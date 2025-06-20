<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'schedule',
        'break_times',
        'tolerance_settings',
        'overtime_settings',
        'applicable_departments',
        'total_hours_per_week',
        'status',
        'metadata'
    ];

    protected $casts = [
        'schedule' => 'array',
        'break_times' => 'array',
        'tolerance_settings' => 'array',
        'overtime_settings' => 'array',
        'applicable_departments' => 'array',
        'total_hours_per_week' => 'decimal:2',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'status' => 'active',
        'tolerance_settings' => '{"late_tolerance_minutes": 15, "early_departure_tolerance_minutes": 15}',
        'overtime_settings' => '{"enabled": false, "multiplier": 1.5, "max_hours_per_day": 4, "requires_approval": true}'
    ];

    /**
     * Get schedule for specific day
     */
    public function getScheduleForDay($day): ?array
    {
        $dayKey = strtolower($day);
        return $this->schedule[$dayKey] ?? null;
    }

    /**
     * Check if work period has schedule for day
     */
    public function hasScheduleForDay($day): bool
    {
        $schedule = $this->getScheduleForDay($day);
        return $schedule && isset($schedule['start']) && isset($schedule['end']);
    }

    /**
     * Get work hours for specific day
     */
    public function getWorkHoursForDay($day): float
    {
        $schedule = $this->getScheduleForDay($day);
        if (!$schedule || !isset($schedule['start']) || !isset($schedule['end'])) {
            return 0;
        }

        $start = Carbon::createFromFormat('H:i', $schedule['start']);
        $end = Carbon::createFromFormat('H:i', $schedule['end']);
        $workMinutes = $start->diffInMinutes($end);

        // Subtract break times
        $breakMinutes = 0;
        foreach ($this->break_times ?? [] as $break) {
            if (isset($break['start']) && isset($break['end'])) {
                $breakStart = Carbon::createFromFormat('H:i', $break['start']);
                $breakEnd = Carbon::createFromFormat('H:i', $break['end']);
                $breakMinutes += $breakStart->diffInMinutes($breakEnd);
            }
        }

        return max(0, ($workMinutes - $breakMinutes) / 60);
    }

    /**
     * Get tolerance for late arrival
     */
    public function getLateTolerance(): int
    {
        return $this->tolerance_settings['late_tolerance_minutes'] ?? 15;
    }

    /**
     * Get tolerance for early departure
     */
    public function getEarlyDepartureTolerance(): int
    {
        return $this->tolerance_settings['early_departure_tolerance_minutes'] ?? 15;
    }

    /**
     * Check if overtime is enabled
     */
    public function isOvertimeEnabled(): bool
    {
        return $this->overtime_settings['enabled'] ?? false;
    }

    /**
     * Get overtime multiplier
     */
    public function getOvertimeMultiplier(): float
    {
        return $this->overtime_settings['multiplier'] ?? 1.5;
    }

    /**
     * Get max overtime hours per day
     */
    public function getMaxOvertimeHours(): int
    {
        return $this->overtime_settings['max_hours_per_day'] ?? 4;
    }

    /**
     * Check if overtime requires approval
     */
    public function requiresOvertimeApproval(): bool
    {
        return $this->overtime_settings['requires_approval'] ?? true;
    }

    /**
     * Get active work period
     */
    public static function getActive()
    {
        return static::where('status', 'active')->first();
    }

    /**
     * Scope: Active work periods
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By department
     */
    public function scopeForDepartment($query, $department)
    {
        return $query->where(function ($q) use ($department) {
            $q->whereNull('applicable_departments')
                ->orWhereJsonContains('applicable_departments', $department);
        });
    }

    /**
     * Check if work period applies to department
     */
    public function appliesToDepartment($department): bool
    {
        if (!$this->applicable_departments) {
            return true; // Applies to all departments
        }

        return in_array($department, $this->applicable_departments);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'archived' => 'Arsip'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Get working days in schedule
     */
    public function getWorkingDaysAttribute(): array
    {
        $workingDays = [];
        foreach ($this->schedule ?? [] as $day => $times) {
            if ($times && isset($times['start']) && isset($times['end'])) {
                $workingDays[] = $day;
            }
        }
        return $workingDays;
    }

    /**
     * Get total break duration in minutes
     */
    public function getTotalBreakMinutesAttribute(): int
    {
        $totalMinutes = 0;
        foreach ($this->break_times ?? [] as $break) {
            if (isset($break['start']) && isset($break['end'])) {
                $start = Carbon::createFromFormat('H:i', $break['start']);
                $end = Carbon::createFromFormat('H:i', $break['end']);
                $totalMinutes += $start->diffInMinutes($end);
            }
        }
        return $totalMinutes;
    }

    /**
     * Get daily working hours (average)
     */
    public function getDailyWorkingHoursAttribute(): float
    {
        $workingDays = count($this->working_days);
        return $workingDays > 0 ? round($this->total_hours_per_week / $workingDays, 2) : 0;
    }
}
