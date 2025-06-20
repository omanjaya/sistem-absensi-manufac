<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Analytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'department',
        'metric_type',
        'metric_name',
        'metric_value',
        'metric_data',
        'period_type',
        'period_start',
        'period_end',
        'calculated_at',
        'context'
    ];

    protected function casts(): array
    {
        return [
            'metric_value' => 'decimal:2',
            'metric_data' => 'array',
            'period_start' => 'date',
            'period_end' => 'date',
            'calculated_at' => 'datetime',
            'context' => 'array',
        ];
    }

    /**
     * Get the user for this analytics record
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for specific metric types
     */
    public function scopeMetricType($query, $type)
    {
        return $query->where('metric_type', $type);
    }

    /**
     * Scope for specific period
     */
    public function scopePeriod($query, $start, $end)
    {
        return $query->whereBetween('period_start', [$start, $end]);
    }

    /**
     * Scope for department
     */
    public function scopeDepartment($query, $department)
    {
        return $query->where('department', $department);
    }

    /**
     * Calculate attendance rate for user/period
     */
    public static function calculateAttendanceRate($userId, $startDate, $endDate)
    {
        $totalWorkDays = Carbon::parse($startDate)->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, Carbon::parse($endDate));

        $presentDays = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->whereIn('status', ['present', 'late'])
            ->count();

        $rate = $totalWorkDays > 0 ? ($presentDays / $totalWorkDays) * 100 : 0;

        return static::create([
            'user_id' => $userId,
            'metric_type' => 'attendance',
            'metric_name' => 'attendance_rate',
            'metric_value' => $rate,
            'metric_data' => [
                'total_work_days' => $totalWorkDays,
                'present_days' => $presentDays,
                'absent_days' => $totalWorkDays - $presentDays
            ],
            'period_type' => 'custom',
            'period_start' => $startDate,
            'period_end' => $endDate,
            'calculated_at' => now()
        ]);
    }

    /**
     * Calculate productivity metrics
     */
    public static function calculateProductivityMetrics($userId, $period = 'month')
    {
        $endDate = now();
        $startDate = match ($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            'quarter' => now()->subQuarter(),
            'year' => now()->subYear(),
            default => now()->subMonth()
        };

        $attendances = Attendance::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalHours = $attendances->sum('work_hours');
        $avgHoursPerDay = $attendances->count() > 0 ? $totalHours / $attendances->count() : 0;
        $overtimeHours = $attendances->sum('overtime_hours');
        $lateArrivals = $attendances->where('status', 'late')->count();

        return static::create([
            'user_id' => $userId,
            'metric_type' => 'productivity',
            'metric_name' => 'work_efficiency',
            'metric_value' => $avgHoursPerDay,
            'metric_data' => [
                'total_hours' => $totalHours,
                'average_hours_per_day' => $avgHoursPerDay,
                'overtime_hours' => $overtimeHours,
                'late_arrivals' => $lateArrivals,
                'attendance_count' => $attendances->count()
            ],
            'period_type' => $period,
            'period_start' => $startDate,
            'period_end' => $endDate,
            'calculated_at' => now()
        ]);
    }
}
