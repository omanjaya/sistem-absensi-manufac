<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'duration_days',
        'type',
        'status',
        'applies_to_all',
        'departments',
        'roles',
        'allow_attendance',
        'overtime_eligible',
        'overtime_multiplier',
        'is_recurring',
        'recurrence_type',
        'recurrence_data',
        'color',
        'metadata',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'applies_to_all' => 'boolean',
        'allow_attendance' => 'boolean',
        'overtime_eligible' => 'boolean',
        'overtime_multiplier' => 'decimal:2',
        'is_recurring' => 'boolean',
        'departments' => 'array',
        'roles' => 'array',
        'recurrence_data' => 'array',
        'metadata' => 'array'
    ];

    protected $attributes = [
        'status' => 'active',
        'applies_to_all' => true,
        'allow_attendance' => false,
        'overtime_eligible' => false,
        'overtime_multiplier' => 1.5,
        'is_recurring' => false,
        'color' => '#dc3545'
    ];

    /**
     * Check if holiday is active for given date
     */
    public function isActiveForDate(Carbon $date): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $date->between($this->start_date, $this->end_date);
    }

    /**
     * Check if holiday applies to user
     */
    public function appliesTo($user): bool
    {
        if ($this->applies_to_all) {
            return true;
        }

        // Check department
        if ($this->departments && $user->department) {
            if (in_array($user->department, $this->departments)) {
                return true;
            }
        }

        // Check role
        if ($this->roles && $user->role) {
            if (in_array($user->role, $this->roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get holidays for specific date
     */
    public static function forDate(Carbon $date, $userId = null)
    {
        $query = static::where('status', 'active')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date);

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('applies_to_all', true)
                        ->orWhere(function ($q2) use ($user) {
                            $q2->whereJsonContains('departments', $user->department)
                                ->orWhereJsonContains('roles', $user->role);
                        });
                });
            }
        }

        return $query->orderBy('start_date');
    }

    /**
     * Get holidays for date range
     */
    public static function forDateRange(Carbon $startDate, Carbon $endDate, $userId = null)
    {
        $query = static::where('status', 'active')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q2) use ($startDate, $endDate) {
                        $q2->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                    });
            });

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                $query->where(function ($q) use ($user) {
                    $q->where('applies_to_all', true)
                        ->orWhere(function ($q2) use ($user) {
                            $q2->whereJsonContains('departments', $user->department)
                                ->orWhereJsonContains('roles', $user->role);
                        });
                });
            }
        }

        return $query->orderBy('start_date');
    }

    /**
     * Scope: Active holidays
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Current year
     */
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('start_date', date('Y'));
    }

    /**
     * Scope: Recurring holidays
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        $types = [
            'national' => 'Hari Libur Nasional',
            'religious' => 'Hari Libur Keagamaan',
            'school' => 'Libur Sekolah',
            'semester' => 'Libur Semester',
            'custom' => 'Libur Khusus'
        ];

        return $types[$this->type] ?? $this->type;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            'active' => 'Aktif',
            'inactive' => 'Tidak Aktif',
            'cancelled' => 'Dibatalkan'
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * Check if holiday is current
     */
    public function getIsCurrentAttribute(): bool
    {
        $today = Carbon::today();
        return $today->between($this->start_date, $this->end_date);
    }

    /**
     * Check if holiday is upcoming
     */
    public function getIsUpcomingAttribute(): bool
    {
        return Carbon::today()->lt($this->start_date);
    }

    /**
     * Check if holiday is past
     */
    public function getIsPastAttribute(): bool
    {
        return Carbon::today()->gt($this->end_date);
    }
}
