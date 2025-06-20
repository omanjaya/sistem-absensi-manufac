<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\Project;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class TimeEntryController extends Controller
{
    /**
     * Get time entries for current user or all (admin)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = TimeEntry::with(['user', 'project']);

        // Filter by user if not admin
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        if ($request->filled('user_id') && $user->isAdmin()) {
            $query->where('user_id', $request->user_id);
        }

        $entries = $query->latest('start_time')->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $entries
        ]);
    }

    /**
     * Get time entries for current user only
     */
    public function myEntries(Request $request)
    {
        $user = Auth::user();

        $entries = TimeEntry::with(['project'])
            ->where('user_id', $user->id)
            ->when($request->filled('project_id'), function ($q) use ($request) {
                $q->where('project_id', $request->project_id);
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest('start_time')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $entries
        ]);
    }

    /**
     * Get active time entry for current user
     */
    public function getActive()
    {
        $user = Auth::user();

        $activeEntry = TimeEntry::with(['project'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused'])
            ->first();

        return response()->json([
            'success' => true,
            'data' => $activeEntry
        ]);
    }

    /**
     * Create new time entry
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => 'nullable|exists:projects,id',
            'task_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'required|date',
            'end_time' => 'nullable|date|after:start_time',
            'entry_type' => 'in:regular,overtime,break,meeting,training',
            'billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Check if user has an active time entry
        $activeEntry = TimeEntry::where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused'])
            ->first();

        if ($activeEntry) {
            return response()->json([
                'success' => false,
                'message' => 'You have an active time entry. Please stop it before starting a new one.',
                'active_entry' => $activeEntry
            ], 422);
        }

        // Calculate duration if end_time is provided
        $duration = null;
        $status = 'active';

        if ($request->end_time) {
            $start = Carbon::parse($request->start_time);
            $end = Carbon::parse($request->end_time);
            $duration = $start->diffInMinutes($end);
            $status = 'completed';
        }

        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $request->project_id,
            'task_name' => $request->task_name,
            'description' => $request->description,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'duration_minutes' => $duration,
            'entry_type' => $request->get('entry_type', 'regular'),
            'status' => $status,
            'billable' => $request->get('billable', true),
            'hourly_rate' => $request->hourly_rate,
            'tags' => $request->tags ?? [],
            'metadata' => [
                'created_via' => 'api',
                'ip_address' => $request->ip()
            ]
        ]);

        AuditLog::logAction('time_entry.created', $entry, [], $entry->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Time entry created successfully',
            'data' => $entry->load(['project', 'user'])
        ], 201);
    }

    /**
     * Quick start time tracking
     */
    public function quickStart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'task_name' => 'required|string|max:255',
            'project_id' => 'nullable|exists:projects,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Stop any active entries
        TimeEntry::where('user_id', $user->id)
            ->whereIn('status', ['active', 'paused'])
            ->update([
                'status' => 'completed',
                'end_time' => now()
            ]);

        // Start new entry
        $entry = TimeEntry::create([
            'user_id' => $user->id,
            'project_id' => $request->project_id,
            'task_name' => $request->task_name,
            'start_time' => now(),
            'status' => 'active',
            'entry_type' => 'regular',
            'billable' => true,
            'metadata' => [
                'created_via' => 'quick_start',
                'ip_address' => $request->ip()
            ]
        ]);

        AuditLog::logAction('time_entry.quick_started', $entry, [], $entry->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Time tracking started',
            'data' => $entry->load('project')
        ]);
    }

    /**
     * Show specific time entry
     */
    public function show($id)
    {
        $user = Auth::user();
        $entry = TimeEntry::with(['user', 'project'])->findOrFail($id);

        // Check access
        if (!$user->isAdmin() && $entry->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $entry
        ]);
    }

    /**
     * Update time entry
     */
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $entry = TimeEntry::findOrFail($id);

        // Check access
        if (!$user->isAdmin() && $entry->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'project_id' => 'nullable|exists:projects,id',
            'task_name' => 'string|max:255',
            'description' => 'nullable|string',
            'start_time' => 'date',
            'end_time' => 'nullable|date|after:start_time',
            'entry_type' => 'in:regular,overtime,break,meeting,training',
            'billable' => 'boolean',
            'hourly_rate' => 'nullable|numeric|min:0',
            'tags' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $entry->toArray();

        // Recalculate duration if times changed
        if ($request->has(['start_time', 'end_time']) && $request->end_time) {
            $start = Carbon::parse($request->start_time ?? $entry->start_time);
            $end = Carbon::parse($request->end_time);
            $request->merge(['duration_minutes' => $start->diffInMinutes($end)]);
        }

        $entry->update($request->only([
            'project_id',
            'task_name',
            'description',
            'start_time',
            'end_time',
            'duration_minutes',
            'entry_type',
            'billable',
            'hourly_rate',
            'tags'
        ]));

        AuditLog::logAction('time_entry.updated', $entry, $oldData, $entry->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Time entry updated successfully',
            'data' => $entry->load(['project', 'user'])
        ]);
    }

    /**
     * Delete time entry
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $entry = TimeEntry::findOrFail($id);

        // Check access
        if (!$user->isAdmin() && $entry->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        AuditLog::logAction('time_entry.deleted', $entry, $entry->toArray(), []);

        $entry->delete();

        return response()->json([
            'success' => true,
            'message' => 'Time entry deleted successfully'
        ]);
    }

    /**
     * Start time entry
     */
    public function start($id)
    {
        $user = Auth::user();
        $entry = TimeEntry::where('user_id', $user->id)->findOrFail($id);

        if ($entry->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Time entry is already active'
            ], 422);
        }

        // Stop any other active entries
        TimeEntry::where('user_id', $user->id)
            ->where('id', '!=', $id)
            ->whereIn('status', ['active', 'paused'])
            ->update(['status' => 'paused']);

        $entry->update([
            'status' => 'active',
            'start_time' => $entry->start_time ?? now()
        ]);

        AuditLog::logAction('time_entry.started', $entry);

        return response()->json([
            'success' => true,
            'message' => 'Time tracking started',
            'data' => $entry
        ]);
    }

    /**
     * Stop time entry
     */
    public function stop($id)
    {
        $user = Auth::user();
        $entry = TimeEntry::where('user_id', $user->id)->findOrFail($id);

        if (!in_array($entry->status, ['active', 'paused'])) {
            return response()->json([
                'success' => false,
                'message' => 'Time entry is not active'
            ], 422);
        }

        $endTime = now();
        $duration = null;

        if ($entry->start_time) {
            $start = Carbon::parse($entry->start_time);
            $duration = $start->diffInMinutes($endTime);
        }

        $entry->update([
            'status' => 'completed',
            'end_time' => $endTime,
            'duration_minutes' => $duration
        ]);

        AuditLog::logAction('time_entry.stopped', $entry, [], [
            'duration_minutes' => $duration
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Time tracking stopped',
            'data' => [
                'entry' => $entry,
                'duration_hours' => $duration ? round($duration / 60, 2) : 0
            ]
        ]);
    }

    /**
     * Pause time entry
     */
    public function pause($id)
    {
        $user = Auth::user();
        $entry = TimeEntry::where('user_id', $user->id)->findOrFail($id);

        if ($entry->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Time entry is not active'
            ], 422);
        }

        $entry->update(['status' => 'paused']);

        AuditLog::logAction('time_entry.paused', $entry);

        return response()->json([
            'success' => true,
            'message' => 'Time tracking paused',
            'data' => $entry
        ]);
    }

    /**
     * Get time entries for specific project
     */
    public function projectEntries($projectId, Request $request)
    {
        $user = Auth::user();
        $project = Project::findOrFail($projectId);

        // Check if user has access to this project
        if (!$user->isAdmin() && $project->manager_id !== $user->id) {
            $hasAccess = TimeEntry::where('user_id', $user->id)
                ->where('project_id', $projectId)
                ->exists();

            if (!$hasAccess) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this project'
                ], 403);
            }
        }

        $query = TimeEntry::with('user')
            ->where('project_id', $projectId);

        // If not admin and not project manager, only show own entries
        if (!$user->isAdmin() && $project->manager_id !== $user->id) {
            $query->where('user_id', $user->id);
        }

        $entries = $query->latest('start_time')
            ->paginate($request->get('per_page', 15));

        // Calculate project time summary
        $summary = [
            'total_entries' => $entries->total(),
            'total_hours' => round($query->sum('duration_minutes') / 60, 2),
            'billable_hours' => round($query->where('billable', true)->sum('duration_minutes') / 60, 2),
            'team_members' => $query->distinct('user_id')->count(),
            'active_entries' => $query->whereIn('status', ['active', 'paused'])->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $entries,
            'summary' => $summary
        ]);
    }
}
