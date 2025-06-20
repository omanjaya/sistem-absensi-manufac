<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'location',
        'class_name',
        'student_count',
        'status',
        'schedule_type',
        'is_recurring',
        'recurrence_type',
        'metadata',
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'is_recurring' => 'boolean',
        'metadata' => 'array'
    ];

    /**
     * Relationship with User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get teacher name
     */
    public function getTeacherNameAttribute(): string
    {
        return $this->user->name;
    }

    /**
     * Get duration in minutes
     */
    public function getDurationMinutesAttribute(): int
    {
        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        return $end->diffInMinutes($start);
    }

    /**
     * Get duration in hours
     */
    public function getDurationHoursAttribute(): float
    {
        return round($this->duration_minutes / 60, 2);
    }

    /**
     * Check if schedule is active for given date
     */
    public function isActiveForDate(Carbon $date): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // Check if date is within schedule period
        if ($date->lt($this->start_date)) {
            return false;
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }

        // Check day of week
        $dayOfWeek = strtolower($date->format('l'));
        return $this->day_of_week === $dayOfWeek;
    }

    /**
     * Get schedules for specific date
     */
    public static function forDate(Carbon $date, $userId = null)
    {
        $dayOfWeek = strtolower($date->format('l'));

        $query = static::where('day_of_week', $dayOfWeek)
            ->where('status', 'active')
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->with('user')->orderBy('start_time');
    }

    /**
     * Get weekly schedule for user
     */
    public static function weeklySchedule($userId, Carbon $startOfWeek = null)
    {
        if (!$startOfWeek) {
            $startOfWeek = Carbon::now()->startOfWeek();
        }

        $endOfWeek = $startOfWeek->copy()->endOfWeek();

        return static::where('user_id', $userId)
            ->where('status', 'active')
            ->where('start_date', '<=', $endOfWeek)
            ->where(function ($q) use ($startOfWeek) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $startOfWeek);
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * Check for conflicts with other schedules
     */
    public function hasConflicts(): bool
    {
        return static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->where('day_of_week', $this->day_of_week)
            ->where('status', 'active')
            ->where('start_date', '<=', $this->end_date ?? '9999-12-31')
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $this->start_date);
            })
            ->where(function ($q) {
                // Time overlap check
                $q->where(function ($q1) {
                    $q1->where('start_time', '<', $this->end_time)
                        ->where('end_time', '>', $this->start_time);
                });
            })
            ->exists();
    }

    /**
     * Scope: Active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: By schedule type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('schedule_type', $type);
    }

    /**
     * Scope: Recurring schedules
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }
}
