<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Salary;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Get dashboard statistics
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            return $this->getAdminStats();
        } else {
            return $this->getEmployeeStats($user);
        }
    }

    /**
     * Get recent attendances
     */
    public function recentAttendances(Request $request)
    {
        $user = $request->user();
        $query = Attendance::with('user:id,name,employee_id,department');

        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        }

        $attendances = $query->latest()
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Get attendance summary for admin
     */
    public function attendanceSummary(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $summary = Attendance::with('user:id,name,employee_id,department')
            ->whereBetween('date', [$startDate, $endDate])
            ->select([
                'user_id',
                DB::raw('COUNT(*) as total_attendances'),
                DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_days'),
                DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_days'),
                DB::raw('SUM(overtime_hours) as total_overtime'),
                DB::raw('AVG(work_hours) as avg_work_hours')
            ])
            ->groupBy('user_id')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $summary,
            'period' => [
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString()
            ]
        ]);
    }

    /**
     * Get monthly statistics
     */
    public function monthlyStats(Request $request)
    {
        $year = $request->get('year', now()->year);

        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = Carbon::createFromDate($year, $month, 1);
            $endDate = $startDate->copy()->endOfMonth();

            $stats = Attendance::whereBetween('date', [$startDate, $endDate])
                ->select([
                    DB::raw('COUNT(*) as total_attendances'),
                    DB::raw('SUM(CASE WHEN status = "present" THEN 1 ELSE 0 END) as present_count'),
                    DB::raw('SUM(CASE WHEN status = "late" THEN 1 ELSE 0 END) as late_count'),
                    DB::raw('SUM(overtime_hours) as total_overtime'),
                    DB::raw('AVG(work_hours) as avg_work_hours')
                ])
                ->first();

            $monthlyData[] = [
                'month' => $month,
                'month_name' => $startDate->format('F'),
                'total_attendances' => $stats->total_attendances ?? 0,
                'present_count' => $stats->present_count ?? 0,
                'late_count' => $stats->late_count ?? 0,
                'total_overtime' => $stats->total_overtime ?? 0,
                'avg_work_hours' => round($stats->avg_work_hours ?? 0, 2)
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $monthlyData,
            'year' => $year
        ]);
    }

    /**
     * Get employee performance data
     */
    public function employeePerformance(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        $performance = User::where('role', 'employee')
            ->where('status', 'active')
            ->with([
                'attendances' => function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('date', [$startDate, $endDate]);
                }
            ])
            ->get()
            ->map(function ($user) use ($startDate, $endDate) {
                $attendances = $user->attendances;
                $workDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
                    return $date->isWeekday();
                }, $endDate);

                $presentDays = $attendances->where('status', 'present')->count();
                $lateDays = $attendances->where('status', 'late')->count();
                $totalOvertimeHours = $attendances->sum('overtime_hours');
                $avgWorkHours = $attendances->avg('work_hours');

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee_id,
                    'department' => $user->department,
                    'work_days' => $workDays,
                    'present_days' => $presentDays,
                    'late_days' => $lateDays,
                    'absent_days' => $workDays - $attendances->count(),
                    'attendance_rate' => $workDays > 0 ? round(($presentDays / $workDays) * 100, 2) : 0,
                    'total_overtime_hours' => $totalOvertimeHours,
                    'avg_work_hours' => round($avgWorkHours ?? 0, 2),
                    'punctuality_rate' => $attendances->count() > 0 ? round(($presentDays / $attendances->count()) * 100, 2) : 0
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $performance,
            'period' => [
                'month' => $month,
                'year' => $year
            ]
        ]);
    }

    /**
     * Get admin dashboard statistics
     */
    private function getAdminStats()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now();
        $thisYear = Carbon::now();

        // Today's statistics
        $todayAttendances = Attendance::whereDate('date', $today)->count();
        $todayPresent = Attendance::whereDate('date', $today)->where('status', 'present')->count();
        $todayLate = Attendance::whereDate('date', $today)->where('status', 'late')->count();

        // This month statistics
        $thisMonthAttendances = Attendance::whereYear('date', $thisMonth->year)
            ->whereMonth('date', $thisMonth->month)
            ->count();

        // Employee statistics
        $totalEmployees = User::where('role', 'employee')->count();
        $activeEmployees = User::where('role', 'employee')->where('status', 'active')->count();

        // Leave statistics
        $pendingLeaves = Leave::where('status', 'pending')->count();
        $thisMonthLeaves = Leave::whereYear('start_date', $thisMonth->year)
            ->whereMonth('start_date', $thisMonth->month)
            ->count();

        // Department statistics
        $departmentStats = User::where('role', 'employee')
            ->where('status', 'active')
            ->select('department', DB::raw('COUNT(*) as count'))
            ->groupBy('department')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'total_attendances' => $todayAttendances,
                    'present' => $todayPresent,
                    'late' => $todayLate,
                    'attendance_rate' => $activeEmployees > 0 ? round(($todayAttendances / $activeEmployees) * 100, 2) : 0
                ],
                'this_month' => [
                    'total_attendances' => $thisMonthAttendances,
                    'total_leaves' => $thisMonthLeaves
                ],
                'employees' => [
                    'total' => $totalEmployees,
                    'active' => $activeEmployees,
                    'inactive' => $totalEmployees - $activeEmployees
                ],
                'leaves' => [
                    'pending' => $pendingLeaves
                ],
                'departments' => $departmentStats
            ]
        ]);
    }

    /**
     * Get employee dashboard statistics
     */
    private function getEmployeeStats(User $user)
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now();

        // Today's attendance
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        // This month statistics
        $thisMonthAttendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $thisMonth->year)
            ->whereMonth('date', $thisMonth->month)
            ->get();

        $workDays = Carbon::now()->startOfMonth()->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, Carbon::now());

        $presentDays = $thisMonthAttendances->where('status', 'present')->count();
        $lateDays = $thisMonthAttendances->where('status', 'late')->count();
        $totalOvertimeHours = $thisMonthAttendances->sum('overtime_hours');

        // Leave statistics
        $pendingLeaves = Leave::where('user_id', $user->id)->where('status', 'pending')->count();
        $approvedLeaves = Leave::where('user_id', $user->id)->where('status', 'approved')
            ->whereYear('start_date', $thisMonth->year)
            ->sum('total_days');

        return response()->json([
            'success' => true,
            'data' => [
                'today' => [
                    'has_clocked_in' => $todayAttendance ? !!$todayAttendance->clock_in : false,
                    'has_clocked_out' => $todayAttendance ? !!$todayAttendance->clock_out : false,
                    'clock_in_time' => $todayAttendance?->clock_in?->format('H:i'),
                    'clock_out_time' => $todayAttendance?->clock_out?->format('H:i'),
                    'status' => $todayAttendance?->status ?? 'absent'
                ],
                'this_month' => [
                    'work_days' => $workDays,
                    'present_days' => $presentDays,
                    'late_days' => $lateDays,
                    'absent_days' => $workDays - $thisMonthAttendances->count(),
                    'attendance_rate' => $workDays > 0 ? round(($presentDays / $workDays) * 100, 2) : 0,
                    'total_overtime_hours' => $totalOvertimeHours
                ],
                'leaves' => [
                    'pending' => $pendingLeaves,
                    'approved_this_year' => $approvedLeaves
                ]
            ]
        ]);
    }
}
