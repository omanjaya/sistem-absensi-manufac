<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\User;
use App\Models\Salary;
use App\Models\Notification;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WorkflowController extends Controller
{
    /**
     * Bulk approve leaves (Admin only)
     */
    public function bulkApproveLeaves(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'leave_ids' => 'required|array|min:1',
            'leave_ids.*' => 'exists:leaves,id',
            'approval_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $admin = Auth::user();
        $leaveIds = $request->leave_ids;
        $approvalNotes = $request->approval_notes;

        DB::beginTransaction();

        try {
            $leaves = Leave::whereIn('id', $leaveIds)
                ->where('status', 'pending')
                ->get();

            if ($leaves->count() !== count($leaveIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some leaves are not found or not in pending status'
                ], 422);
            }

            $approvedCount = 0;
            $notifications = [];

            foreach ($leaves as $leave) {
                $leave->update([
                    'status' => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                    'approval_notes' => $approvalNotes
                ]);

                // Create notification for employee
                $notification = Notification::create([
                    'user_id' => $leave->user_id,
                    'title' => 'Leave Request Approved',
                    'message' => "Your leave request from {$leave->start_date} to {$leave->end_date} has been approved.",
                    'type' => 'leave_approved',
                    'priority' => 'high',
                    'reference_type' => Leave::class,
                    'reference_id' => $leave->id,
                    'status' => 'sent',
                    'sent_at' => now()
                ]);

                $notifications[] = $notification;
                $approvedCount++;

                AuditLog::logAction(
                    'leave.bulk_approved',
                    $leave,
                    ['status' => 'pending'],
                    ['status' => 'approved', 'approved_by' => $admin->id]
                );
            }

            AuditLog::logAction('workflow.bulk_approve_leaves', null, [], [
                'approved_count' => $approvedCount,
                'admin_id' => $admin->id,
                'leave_ids' => $leaveIds
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully approved {$approvedCount} leave requests",
                'data' => [
                    'approved_count' => $approvedCount,
                    'notifications_sent' => count($notifications)
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk approve leaves: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk generate salaries (Admin only)
     */
    public function bulkGenerateSalaries(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $admin = Auth::user();
        $userIds = $request->user_ids;
        $periodStart = $request->period_start;
        $periodEnd = $request->period_end;
        $notes = $request->notes;

        DB::beginTransaction();

        try {
            $users = User::whereIn('id', $userIds)
                ->where('status', 'active')
                ->get();

            if ($users->count() !== count($userIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some users are not found or not active'
                ], 422);
            }

            $generatedCount = 0;
            $totalAmount = 0;
            $notifications = [];

            foreach ($users as $user) {
                // Check if salary already exists for this period
                $existingSalary = Salary::where('user_id', $user->id)
                    ->where('period_start', $periodStart)
                    ->where('period_end', $periodEnd)
                    ->first();

                if ($existingSalary) {
                    continue; // Skip if already exists
                }

                // Calculate salary data
                $salaryData = $this->calculateSalaryData($user, $periodStart, $periodEnd);

                $salary = Salary::create([
                    'user_id' => $user->id,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                    'basic_salary' => $salaryData['basic_salary'],
                    'total_days' => $salaryData['total_days'],
                    'work_days' => $salaryData['work_days'],
                    'present_days' => $salaryData['present_days'],
                    'absent_days' => $salaryData['absent_days'],
                    'late_days' => $salaryData['late_days'],
                    'overtime_hours' => $salaryData['overtime_hours'],
                    'overtime_amount' => $salaryData['overtime_amount'],
                    'allowances' => $salaryData['allowances'],
                    'deductions' => $salaryData['deductions'],
                    'gross_salary' => $salaryData['gross_salary'],
                    'tax_amount' => $salaryData['tax_amount'],
                    'net_salary' => $salaryData['net_salary'],
                    'generated_by' => $admin->id,
                    'status' => 'generated',
                    'notes' => $notes
                ]);

                // Create notification for employee
                $notification = Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Salary Generated',
                    'message' => "Your salary for period {$periodStart} to {$periodEnd} has been generated. Net amount: " . number_format($salary->net_salary, 0, ',', '.'),
                    'type' => 'salary_generated',
                    'priority' => 'high',
                    'reference_type' => Salary::class,
                    'reference_id' => $salary->id,
                    'status' => 'sent',
                    'sent_at' => now()
                ]);

                $notifications[] = $notification;
                $generatedCount++;
                $totalAmount += $salary->net_salary;

                AuditLog::logAction('salary.bulk_generated', $salary, [], $salary->toArray());
            }

            AuditLog::logAction('workflow.bulk_generate_salaries', null, [], [
                'generated_count' => $generatedCount,
                'total_amount' => $totalAmount,
                'admin_id' => $admin->id,
                'period' => "{$periodStart} to {$periodEnd}"
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully generated {$generatedCount} salary records",
                'data' => [
                    'generated_count' => $generatedCount,
                    'total_amount' => $totalAmount,
                    'notifications_sent' => count($notifications)
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to bulk generate salaries: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Schedule notifications (Admin only)
     */
    public function scheduleNotifications(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'title' => 'required|string|max:255',
            'message' => 'required|string',
            'type' => 'required|in:attendance_reminder,leave_approved,leave_rejected,overtime_alert,salary_generated,schedule_changed,holiday_announced,system_maintenance,security_alert,birthday_reminder,performance_review,training_reminder',
            'priority' => 'in:low,medium,high,urgent',
            'scheduled_at' => 'required|date|after:now',
            'expires_at' => 'nullable|date|after:scheduled_at',
            'recurring' => 'boolean',
            'recurring_pattern' => 'nullable|in:daily,weekly,monthly',
            'recurring_end' => 'nullable|date|after:scheduled_at'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $admin = Auth::user();
        $userIds = $request->user_ids;
        $scheduledCount = 0;

        DB::beginTransaction();

        try {
            foreach ($userIds as $userId) {
                $notification = Notification::create([
                    'user_id' => $userId,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                    'priority' => $request->get('priority', 'medium'),
                    'channel' => 'app',
                    'scheduled_at' => $request->scheduled_at,
                    'expires_at' => $request->expires_at,
                    'status' => 'pending',
                    'data' => [
                        'scheduled_by' => $admin->id,
                        'recurring' => $request->get('recurring', false),
                        'recurring_pattern' => $request->recurring_pattern,
                        'recurring_end' => $request->recurring_end
                    ]
                ]);

                $scheduledCount++;

                // Handle recurring notifications
                if ($request->get('recurring', false) && $request->recurring_pattern) {
                    $this->createRecurringNotifications($notification, $request);
                }
            }

            AuditLog::logAction('workflow.schedule_notifications', null, [], [
                'scheduled_count' => $scheduledCount,
                'admin_id' => $admin->id,
                'type' => $request->type,
                'scheduled_at' => $request->scheduled_at
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully scheduled {$scheduledCount} notifications",
                'data' => [
                    'scheduled_count' => $scheduledCount,
                    'scheduled_at' => $request->scheduled_at
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Failed to schedule notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get approval queue (Admin only)
     */
    public function getApprovalQueue(Request $request)
    {
        $queue = [
            'pending_leaves' => Leave::with('user')
                ->where('status', 'pending')
                ->latest()
                ->take(10)
                ->get(),
            'pending_salary_approvals' => Salary::with('user')
                ->where('status', 'generated')
                ->latest()
                ->take(10)
                ->get(),
            'pending_notifications' => Notification::with('user')
                ->where('status', 'pending')
                ->where('scheduled_at', '<=', now()->addHours(24))
                ->latest()
                ->take(10)
                ->get()
        ];

        $summary = [
            'total_pending_leaves' => Leave::where('status', 'pending')->count(),
            'total_pending_salaries' => Salary::where('status', 'generated')->count(),
            'total_scheduled_notifications' => Notification::where('status', 'pending')->count(),
            'urgent_items' => Notification::where('priority', 'urgent')
                ->where('status', 'pending')
                ->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $queue,
            'summary' => $summary
        ]);
    }

    /**
     * Batch operations (Admin only)
     */
    public function batchOperations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'operation' => 'required|in:delete_users,archive_attendances,cleanup_notifications,export_data',
            'criteria' => 'required|array',
            'confirm' => 'required|boolean|accepted'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $operation = $request->operation;
        $criteria = $request->criteria;
        $admin = Auth::user();

        try {
            $result = match ($operation) {
                'delete_users' => $this->batchDeleteUsers($criteria),
                'archive_attendances' => $this->batchArchiveAttendances($criteria),
                'cleanup_notifications' => $this->batchCleanupNotifications($criteria),
                'export_data' => $this->batchExportData($criteria),
                default => ['success' => false, 'message' => 'Unknown operation']
            };

            AuditLog::logAction('workflow.batch_operation', null, [], [
                'operation' => $operation,
                'criteria' => $criteria,
                'result' => $result,
                'admin_id' => $admin->id
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Batch operation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get workflow status
     */
    public function getWorkflowStatus()
    {
        $status = [
            'active_workflows' => [
                'scheduled_notifications' => Notification::where('status', 'pending')->count(),
                'pending_approvals' => Leave::where('status', 'pending')->count(),
                'salary_generation_queue' => User::where('status', 'active')->count()
            ],
            'completed_today' => [
                'approved_leaves' => Leave::where('status', 'approved')
                    ->whereDate('approved_at', today())
                    ->count(),
                'generated_salaries' => Salary::whereDate('created_at', today())->count(),
                'sent_notifications' => Notification::where('status', 'sent')
                    ->whereDate('sent_at', today())
                    ->count()
            ],
            'system_health' => [
                'notification_queue_size' => Notification::where('status', 'pending')->count(),
                'failed_operations' => 0, // In production, track failed operations
                'average_processing_time' => '2.5s', // In production, calculate actual time
                'last_maintenance' => now()->subDays(7)
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $status
        ]);
    }

    /**
     * Calculate salary data for user
     */
    private function calculateSalaryData($user, $periodStart, $periodEnd)
    {
        $basicSalary = $user->basic_salary ?? 5000000; // Default basic salary

        $startDate = Carbon::parse($periodStart);
        $endDate = Carbon::parse($periodEnd);
        $totalDays = $startDate->diffInDays($endDate) + 1;

        // Get work days (excluding weekends)
        $workDays = 0;
        $current = $startDate->copy();
        while ($current <= $endDate) {
            if ($current->isWeekday()) {
                $workDays++;
            }
            $current->addDay();
        }

        // Get attendance data
        $attendances = $user->attendances()
            ->whereBetween('date', [$periodStart, $periodEnd])
            ->get();

        $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
        $absentDays = $workDays - $presentDays;
        $lateDays = $attendances->where('status', 'late')->count();
        $overtimeHours = $attendances->sum('overtime_hours');

        // Calculate amounts
        $dailySalary = $basicSalary / 30; // Assume 30 days per month
        $overtimeRate = 25000; // Per hour
        $overtimeAmount = $overtimeHours * $overtimeRate;

        $allowances = 500000; // Fixed allowances
        $deductions = $absentDays * $dailySalary; // Deduct for absent days

        $grossSalary = $basicSalary + $overtimeAmount + $allowances - $deductions;
        $taxAmount = $grossSalary * 0.05; // 5% tax
        $netSalary = $grossSalary - $taxAmount;

        return [
            'basic_salary' => $basicSalary,
            'total_days' => $totalDays,
            'work_days' => $workDays,
            'present_days' => $presentDays,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'overtime_hours' => $overtimeHours,
            'overtime_amount' => $overtimeAmount,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'gross_salary' => $grossSalary,
            'tax_amount' => $taxAmount,
            'net_salary' => max(0, $netSalary) // Ensure non-negative
        ];
    }

    /**
     * Create recurring notifications
     */
    private function createRecurringNotifications($baseNotification, $request)
    {
        $pattern = $request->recurring_pattern;
        $endDate = Carbon::parse($request->recurring_end ?? now()->addMonths(3));
        $current = Carbon::parse($request->scheduled_at);

        $count = 0;
        while ($current <= $endDate && $count < 100) { // Limit to 100 occurrences
            $interval = match ($pattern) {
                'daily' => $current->addDay(),
                'weekly' => $current->addWeek(),
                'monthly' => $current->addMonth(),
                default => null
            };

            if ($interval === null) break;

            if ($current <= $endDate) {
                Notification::create([
                    'user_id' => $baseNotification->user_id,
                    'title' => $baseNotification->title,
                    'message' => $baseNotification->message,
                    'type' => $baseNotification->type,
                    'priority' => $baseNotification->priority,
                    'channel' => $baseNotification->channel,
                    'scheduled_at' => $current,
                    'expires_at' => $baseNotification->expires_at,
                    'status' => 'pending',
                    'data' => array_merge($baseNotification->data ?? [], [
                        'recurring_sequence' => $count + 1,
                        'parent_notification_id' => $baseNotification->id
                    ])
                ]);

                $count++;
            }
        }
    }

    /**
     * Batch delete users
     */
    private function batchDeleteUsers($criteria)
    {
        // Implementation for batch user deletion
        return ['success' => true, 'message' => 'Feature not implemented yet'];
    }

    /**
     * Batch archive attendances
     */
    private function batchArchiveAttendances($criteria)
    {
        // Implementation for batch attendance archiving
        return ['success' => true, 'message' => 'Feature not implemented yet'];
    }

    /**
     * Batch cleanup notifications
     */
    private function batchCleanupNotifications($criteria)
    {
        // Implementation for batch notification cleanup
        return ['success' => true, 'message' => 'Feature not implemented yet'];
    }

    /**
     * Batch export data
     */
    private function batchExportData($criteria)
    {
        // Implementation for batch data export
        return ['success' => true, 'message' => 'Feature not implemented yet'];
    }
}
