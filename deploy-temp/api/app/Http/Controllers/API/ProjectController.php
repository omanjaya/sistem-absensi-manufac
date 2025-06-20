<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\TimeEntry;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ProjectController extends Controller
{
    /**
     * Get all projects (Admin) or user's projects (Employee)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Project::with(['manager', 'timeEntries']);

        // If not admin, only show projects where user is assigned
        if (!$user->isAdmin()) {
            $query->whereHas('timeEntries', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('code', 'like', '%' . $request->search . '%')
                    ->orWhere('client_name', 'like', '%' . $request->search . '%');
            });
        }

        $projects = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Get projects assigned to current user
     */
    public function myProjects(Request $request)
    {
        $user = Auth::user();

        $projects = Project::with(['manager', 'timeEntries'])
            ->whereHas('timeEntries', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->orWhere('manager_id', $user->id)
            ->when($request->filled('status'), function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $projects
        ]);
    }

    /**
     * Create new project (Admin only)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:projects,code',
            'description' => 'nullable|string',
            'manager_id' => 'required|exists:users,id',
            'status' => 'in:planning,active,on_hold,completed,cancelled',
            'priority' => 'in:low,medium,high,critical',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'client_name' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $project = Project::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'manager_id' => $request->manager_id,
            'status' => $request->get('status', 'planning'),
            'priority' => $request->get('priority', 'medium'),
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'budget' => $request->budget,
            'hourly_rate' => $request->hourly_rate,
            'client_name' => $request->client_name,
            'settings' => $request->settings ?? [],
            'metadata' => $request->metadata ?? []
        ]);

        AuditLog::logAction('project.created', $project, [], $project->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Project created successfully',
            'data' => $project->load('manager')
        ], 201);
    }

    /**
     * Get specific project
     */
    public function show($id)
    {
        $user = Auth::user();
        $project = Project::with(['manager', 'timeEntries.user'])->findOrFail($id);

        // Check if user has access to this project
        if (!$user->isAdmin() && $project->manager_id !== $user->id) {
            $hasTimeEntries = $project->timeEntries()->where('user_id', $user->id)->exists();
            if (!$hasTimeEntries) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this project'
                ], 403);
            }
        }

        // Calculate project statistics
        $stats = $this->calculateProjectStats($project);

        return response()->json([
            'success' => true,
            'data' => [
                'project' => $project,
                'statistics' => $stats
            ]
        ]);
    }

    /**
     * Update project (Admin only)
     */
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'code' => 'string|max:50|unique:projects,code,' . $id,
            'description' => 'nullable|string',
            'manager_id' => 'exists:users,id',
            'status' => 'in:planning,active,on_hold,completed,cancelled',
            'priority' => 'in:low,medium,high,critical',
            'start_date' => 'date',
            'end_date' => 'nullable|date|after:start_date',
            'budget' => 'nullable|numeric|min:0',
            'hourly_rate' => 'nullable|numeric|min:0',
            'client_name' => 'nullable|string|max:255',
            'settings' => 'nullable|array',
            'metadata' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $oldData = $project->toArray();

        $project->update($request->only([
            'name',
            'code',
            'description',
            'manager_id',
            'status',
            'priority',
            'start_date',
            'end_date',
            'budget',
            'hourly_rate',
            'client_name',
            'settings',
            'metadata'
        ]));

        AuditLog::logAction('project.updated', $project, $oldData, $project->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Project updated successfully',
            'data' => $project->load('manager')
        ]);
    }

    /**
     * Delete project (Admin only)
     */
    public function destroy($id)
    {
        $project = Project::findOrFail($id);

        // Check if project has time entries
        $timeEntriesCount = $project->timeEntries()->count();
        if ($timeEntriesCount > 0) {
            return response()->json([
                'success' => false,
                'message' => "Cannot delete project with {$timeEntriesCount} time entries. Please archive instead."
            ], 422);
        }

        AuditLog::logAction('project.deleted', $project, $project->toArray(), []);

        $project->delete();

        return response()->json([
            'success' => true,
            'message' => 'Project deleted successfully'
        ]);
    }

    /**
     * Get project analytics (Admin only)
     */
    public function analytics($id)
    {
        $project = Project::with(['timeEntries.user', 'manager'])->findOrFail($id);

        $analytics = [
            'overview' => $this->calculateProjectStats($project),
            'time_tracking' => $this->getTimeTrackingAnalytics($project),
            'team_performance' => $this->getTeamPerformanceAnalytics($project),
            'financial_summary' => $this->getFinancialSummary($project),
            'timeline_analysis' => $this->getTimelineAnalysis($project)
        ];

        AuditLog::logAction('project.analytics_viewed', $project);

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    /**
     * Calculate project statistics
     */
    private function calculateProjectStats($project)
    {
        $timeEntries = $project->timeEntries;

        $totalHours = $timeEntries->sum('duration_minutes') / 60;
        $billableHours = $timeEntries->where('billable', true)->sum('duration_minutes') / 60;
        $totalCost = $billableHours * ($project->hourly_rate ?? 0);

        $uniqueMembers = $timeEntries->pluck('user_id')->unique()->count();

        $completedEntries = $timeEntries->where('status', 'completed')->count();
        $totalEntries = $timeEntries->count();

        $progress = $totalEntries > 0 ? ($completedEntries / $totalEntries) * 100 : 0;

        return [
            'total_hours' => round($totalHours, 2),
            'billable_hours' => round($billableHours, 2),
            'non_billable_hours' => round($totalHours - $billableHours, 2),
            'total_cost' => round($totalCost, 2),
            'team_members' => $uniqueMembers,
            'total_tasks' => $totalEntries,
            'completed_tasks' => $completedEntries,
            'progress_percentage' => round($progress, 2),
            'budget_utilization' => $project->budget ? round(($totalCost / $project->budget) * 100, 2) : null,
            'status' => $project->status,
            'priority' => $project->priority
        ];
    }

    /**
     * Get time tracking analytics
     */
    private function getTimeTrackingAnalytics($project)
    {
        $timeEntries = $project->timeEntries()->with('user')->get();

        // Daily time tracking
        $dailyHours = $timeEntries->groupBy(function ($entry) {
            return $entry->start_time->format('Y-m-d');
        })->map(function ($dayEntries) {
            return [
                'total_hours' => round($dayEntries->sum('duration_minutes') / 60, 2),
                'billable_hours' => round($dayEntries->where('billable', true)->sum('duration_minutes') / 60, 2),
                'entries_count' => $dayEntries->count()
            ];
        });

        // Weekly trends
        $weeklyTrends = $timeEntries->groupBy(function ($entry) {
            return $entry->start_time->format('Y-W');
        })->map(function ($weekEntries) {
            return round($weekEntries->sum('duration_minutes') / 60, 2);
        });

        return [
            'daily_breakdown' => $dailyHours,
            'weekly_trends' => $weeklyTrends,
            'average_daily_hours' => $dailyHours->count() > 0 ? round($dailyHours->avg('total_hours'), 2) : 0,
            'peak_day' => $dailyHours->sortByDesc('total_hours')->keys()->first()
        ];
    }

    /**
     * Get team performance analytics
     */
    private function getTeamPerformanceAnalytics($project)
    {
        $timeEntries = $project->timeEntries()->with('user')->get();

        $memberStats = $timeEntries->groupBy('user_id')->map(function ($userEntries, $userId) {
            $user = $userEntries->first()->user;
            $totalMinutes = $userEntries->sum('duration_minutes');
            $billableMinutes = $userEntries->where('billable', true)->sum('duration_minutes');

            return [
                'user' => $user->only(['id', 'name', 'department']),
                'total_hours' => round($totalMinutes / 60, 2),
                'billable_hours' => round($billableMinutes / 60, 2),
                'task_count' => $userEntries->count(),
                'completed_tasks' => $userEntries->where('status', 'completed')->count(),
                'avg_task_duration' => $userEntries->count() > 0 ? round($totalMinutes / $userEntries->count() / 60, 2) : 0,
                'efficiency_score' => $this->calculateEfficiencyScore($userEntries)
            ];
        })->values();

        return [
            'team_members' => $memberStats,
            'top_contributor' => $memberStats->sortByDesc('total_hours')->first(),
            'most_efficient' => $memberStats->sortByDesc('efficiency_score')->first()
        ];
    }

    /**
     * Get financial summary
     */
    private function getFinancialSummary($project)
    {
        $timeEntries = $project->timeEntries;
        $billableHours = $timeEntries->where('billable', true)->sum('duration_minutes') / 60;
        $hourlyRate = $project->hourly_rate ?? 0;

        $revenue = $billableHours * $hourlyRate;
        $budgetUsed = $project->budget ? ($revenue / $project->budget) * 100 : 0;

        return [
            'total_revenue' => round($revenue, 2),
            'hourly_rate' => $hourlyRate,
            'billable_hours' => round($billableHours, 2),
            'budget' => $project->budget,
            'budget_utilization' => round($budgetUsed, 2),
            'remaining_budget' => $project->budget ? round($project->budget - $revenue, 2) : null,
            'projected_total' => $this->calculateProjectedTotal($project)
        ];
    }

    /**
     * Get timeline analysis
     */
    private function getTimelineAnalysis($project)
    {
        $now = now();
        $startDate = Carbon::parse($project->start_date);
        $endDate = $project->end_date ? Carbon::parse($project->end_date) : null;

        $totalDays = $endDate ? $startDate->diffInDays($endDate) : null;
        $elapsedDays = $startDate->diffInDays($now);
        $remainingDays = $endDate ? $now->diffInDays($endDate) : null;

        $timeProgress = $totalDays ? ($elapsedDays / $totalDays) * 100 : 0;

        return [
            'start_date' => $project->start_date,
            'end_date' => $project->end_date,
            'total_days' => $totalDays,
            'elapsed_days' => $elapsedDays,
            'remaining_days' => $remainingDays,
            'time_progress' => round($timeProgress, 2),
            'is_overdue' => $endDate ? $now->gt($endDate) : false,
            'days_overdue' => $endDate && $now->gt($endDate) ? $now->diffInDays($endDate) : 0
        ];
    }

    /**
     * Calculate efficiency score for user
     */
    private function calculateEfficiencyScore($userEntries)
    {
        $completedTasks = $userEntries->where('status', 'completed')->count();
        $totalTasks = $userEntries->count();
        $avgDuration = $userEntries->avg('duration_minutes');

        if ($totalTasks === 0) return 0;

        $completionRate = ($completedTasks / $totalTasks) * 100;
        $speedScore = $avgDuration ? min(100, (480 / $avgDuration) * 100) : 0; // 8 hours as baseline

        return round(($completionRate + $speedScore) / 2, 2);
    }

    /**
     * Calculate projected total cost
     */
    private function calculateProjectedTotal($project)
    {
        $timeEntries = $project->timeEntries;
        if ($timeEntries->count() === 0) return 0;

        $avgDailyHours = $timeEntries->groupBy(function ($entry) {
            return $entry->start_time->format('Y-m-d');
        })->avg(function ($dayEntries) {
            return $dayEntries->sum('duration_minutes') / 60;
        });

        $remainingDays = $project->end_date ? now()->diffInDays(Carbon::parse($project->end_date)) : 30;
        $projectedHours = $avgDailyHours * $remainingDays;
        $projectedCost = $projectedHours * ($project->hourly_rate ?? 0);

        $currentCost = ($timeEntries->where('billable', true)->sum('duration_minutes') / 60) * ($project->hourly_rate ?? 0);

        return round($currentCost + $projectedCost, 2);
    }
}
