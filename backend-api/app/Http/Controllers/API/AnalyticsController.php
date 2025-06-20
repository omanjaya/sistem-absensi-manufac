<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\Leave;
use App\Models\Salary;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    /**
     * Dashboard analytics
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Basic analytics data
        $data = [
            'total_employees' => User::where('role', 'employee')->count(),
            'total_attendances' => Attendance::count(),
            'total_leaves' => Leave::count(),
            'attendance_rate' => $this->getAttendanceRate(),
        ];

        if ($user->role === 'employee') {
            // Employee specific analytics
            $data['my_attendances'] = Attendance::where('user_id', $user->id)->count();
            $data['my_leaves'] = Leave::where('user_id', $user->id)->count();
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Attendance trends
     */
    public function attendanceTrends(Request $request)
    {
        $trends = Attendance::selectRaw('DATE(date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trends
        ]);
    }

    /**
     * My performance (for employees)
     */
    public function myPerformance(Request $request)
    {
        $user = $request->user();

        $performance = [
            'attendances' => Attendance::where('user_id', $user->id)->count(),
            'leaves' => Leave::where('user_id', $user->id)->count(),
            'attendance_rate' => $this->getUserAttendanceRate($user->id),
        ];

        return response()->json([
            'success' => true,
            'data' => $performance
        ]);
    }

    /**
     * Export analytics (admin only)
     */
    public function export(Request $request)
    {
        // Basic export functionality
        return response()->json([
            'success' => true,
            'message' => 'Export functionality not implemented yet'
        ]);
    }

    /**
     * Productivity metrics (admin only)
     */
    public function productivityMetrics(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Financial overview (admin only)
     */
    public function financialOverview(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Predictions (admin only)
     */
    public function predictions(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Department analytics (admin only)
     */
    public function departmentAnalytics(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => []
        ]);
    }

    /**
     * Calculate overall attendance rate
     */
    private function getAttendanceRate()
    {
        $totalExpected = User::where('role', 'employee')->count() * 30; // Assuming 30 working days
        $totalPresent = Attendance::whereIn('status', ['present', 'late'])->count();

        if ($totalExpected == 0) return 0;

        return round(($totalPresent / $totalExpected) * 100, 2);
    }

    /**
     * Calculate user specific attendance rate
     */
    private function getUserAttendanceRate($userId)
    {
        $totalDays = 30; // Assuming 30 working days
        $presentDays = Attendance::where('user_id', $userId)
            ->whereIn('status', ['present', 'late'])
            ->count();

        return round(($presentDays / $totalDays) * 100, 2);
    }
}
