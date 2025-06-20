<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class LeaveController extends Controller
{
    /**
     * Display a listing of leaves
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Leave::with('user:id,name,employee_id,department', 'approvedBy:id,name');

        // If employee, only show their own leaves
        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('end_date', '<=', $request->date_to);
        }

        // Sort by latest first
        $query->orderBy('created_at', 'desc');

        $leaves = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $leaves
        ]);
    }

    /**
     * Store a newly created leave request
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:annual,sick,emergency,maternity,personal',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:1000',
            'emergency_contact' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $startDate = Carbon::parse($request->start_date);
        $endDate = Carbon::parse($request->end_date);

        try {
            // Check for overlapping leave requests
            $overlapping = Leave::where('user_id', $user->id)
                ->where('status', '!=', 'rejected')
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                        ->orWhereBetween('end_date', [$startDate, $endDate])
                        ->orWhere(function ($q) use ($startDate, $endDate) {
                            $q->where('start_date', '<=', $startDate)
                                ->where('end_date', '>=', $endDate);
                        });
                })
                ->exists();

            if ($overlapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'You already have a leave request for these dates'
                ], 400);
            }

            // Calculate total days
            $totalDays = $startDate->diffInDays($endDate) + 1;

            // Handle file attachment
            $attachmentPath = null;
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                $attachmentPath = $file->storeAs('public/leave_attachments', $filename);
            }

            $leave = Leave::create([
                'user_id' => $user->id,
                'type' => $request->type,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'reason' => $request->reason,
                'emergency_contact' => $request->emergency_contact,
                'attachment' => $attachmentPath,
                'status' => 'pending'
            ]);

            Log::info('Leave request created', [
                'leave_id' => $leave->id,
                'user_id' => $user->id,
                'type' => $leave->type,
                'total_days' => $totalDays
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully',
                'data' => $leave->load('user:id,name,employee_id')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Leave request creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Leave request submission failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Display the specified leave
     */
    public function show(Request $request, Leave $leave)
    {
        $user = $request->user();

        // Employees can only view their own leaves
        if ($user->role === 'employee' && $leave->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $leave->load([
                'user:id,name,employee_id,department,position',
                'approvedBy:id,name'
            ])
        ]);
    }

    /**
     * Update the specified leave (employee can only update pending requests)
     */
    public function update(Request $request, Leave $leave)
    {
        $user = $request->user();

        // Check permissions
        if ($user->role === 'employee') {
            if ($leave->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only update pending leave requests'
                ], 400);
            }
        }

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:annual,sick,emergency,maternity,personal',
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'reason' => 'sometimes|string|max:1000',
            'emergency_contact' => 'nullable|string|max:255',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only([
                'type',
                'start_date',
                'end_date',
                'reason',
                'emergency_contact'
            ]);

            // Recalculate total days if dates changed
            if ($request->filled('start_date') || $request->filled('end_date')) {
                $startDate = Carbon::parse($request->start_date ?? $leave->start_date);
                $endDate = Carbon::parse($request->end_date ?? $leave->end_date);
                $updateData['total_days'] = $startDate->diffInDays($endDate) + 1;
            }

            // Handle file attachment
            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $filename = time() . '_' . $user->id . '.' . $file->getClientOriginalExtension();
                $updateData['attachment'] = $file->storeAs('public/leave_attachments', $filename);
            }

            $leave->update($updateData);

            Log::info('Leave request updated', [
                'leave_id' => $leave->id,
                'updated_by' => $user->id,
                'changes' => array_keys($updateData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave request updated successfully',
                'data' => $leave->fresh()->load('user:id,name,employee_id')
            ]);
        } catch (\Exception $e) {
            Log::error('Leave update failed', [
                'leave_id' => $leave->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Leave update failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Remove the specified leave (only pending requests)
     */
    public function destroy(Request $request, Leave $leave)
    {
        $user = $request->user();

        // Check permissions
        if ($user->role === 'employee') {
            if ($leave->user_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 403);
            }

            if ($leave->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only delete pending leave requests'
                ], 400);
            }
        }

        try {
            Log::info('Leave request deleted', [
                'leave_id' => $leave->id,
                'deleted_by' => $user->id
            ]);

            $leave->delete();

            return response()->json([
                'success' => true,
                'message' => 'Leave request deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Leave deletion failed', [
                'leave_id' => $leave->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Leave deletion failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Approve leave request (admin only)
     */
    public function approve(Request $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending leave requests can be approved'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leave->update([
                'status' => 'approved',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes
            ]);

            Log::info('Leave request approved', [
                'leave_id' => $leave->id,
                'approved_by' => $request->user()->id,
                'user_id' => $leave->user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave request approved successfully',
                'data' => $leave->fresh()->load(['user:id,name,employee_id', 'approvedBy:id,name'])
            ]);
        } catch (\Exception $e) {
            Log::error('Leave approval failed', [
                'leave_id' => $leave->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Leave approval failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Reject leave request (admin only)
     */
    public function reject(Request $request, Leave $leave)
    {
        if ($leave->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending leave requests can be rejected'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'approval_notes' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $leave->update([
                'status' => 'rejected',
                'approved_by' => $request->user()->id,
                'approved_at' => now(),
                'approval_notes' => $request->approval_notes
            ]);

            Log::info('Leave request rejected', [
                'leave_id' => $leave->id,
                'rejected_by' => $request->user()->id,
                'user_id' => $leave->user_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave request rejected',
                'data' => $leave->fresh()->load(['user:id,name,employee_id', 'approvedBy:id,name'])
            ]);
        } catch (\Exception $e) {
            Log::error('Leave rejection failed', [
                'leave_id' => $leave->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Leave rejection failed. Please try again.'
            ], 500);
        }
    }
}
