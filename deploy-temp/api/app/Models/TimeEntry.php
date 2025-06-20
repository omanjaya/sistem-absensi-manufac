<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class TimeEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'project_id',
        'attendance_id',
        'task_name',
        'description',
        'start_time',
        'end_time',
        'duration_minutes',
        'entry_type',
        'status',
        'hourly_rate',
        'billable',
        'tags',
        'metadata'
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'duration_minutes' => 'integer',
            'hourly_rate' => 'decimal:2',
            'billable' => 'boolean',
            'tags' => 'array',
            'metadata' => 'array',
        ];
    }

    /**
     * Get the user that owns the time entry
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project associated with the time entry
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the attendance record associated with the time entry
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Scope for active time entries
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for completed time entries
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for billable time entries
     */
    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    /**
     * Scope for specific date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('start_time', [$startDate, $endDate]);
    }

    /**
     * Get duration in hours
     */
    public function getDurationHoursAttribute()
    {
        return $this->duration_minutes ? round($this->duration_minutes / 60, 2) : 0;
    }

    /**
     * Get total cost for this time entry
     */
    public function getTotalCostAttribute()
    {
        if (!$this->billable || !$this->hourly_rate || !$this->duration_minutes) {
            return 0;
        }

        return round(($this->duration_minutes / 60) * $this->hourly_rate, 2);
    }

    /**
     * Check if time entry is currently running
     */
    public function isRunning()
    {
        return in_array($this->status, ['active', 'paused']);
    }

    /**
     * Calculate and update duration
     */
    public function calculateDuration()
    {
        if ($this->start_time && $this->end_time) {
            $start = Carbon::parse($this->start_time);
            $end = Carbon::parse($this->end_time);
            $this->duration_minutes = $start->diffInMinutes($end);
            $this->save();
        }
    }

    /**
     * Stop the time entry
     */
    public function stop()
    {
        if ($this->isRunning()) {
            $this->update([
                'status' => 'completed',
                'end_time' => now()
            ]);
            $this->calculateDuration();
        }
    }

    /**
     * Start the time entry
     */
    public function start()
    {
        if ($this->status !== 'active') {
            $this->update([
                'status' => 'active',
                'start_time' => $this->start_time ?? now()
            ]);
        }
    }

    /**
     * Pause the time entry
     */
    public function pause()
    {
        if ($this->status === 'active') {
            $this->update(['status' => 'paused']);
        }
    }
}
