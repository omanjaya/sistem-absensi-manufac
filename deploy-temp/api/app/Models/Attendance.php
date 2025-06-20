<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'work_hours',
        'status',
        'clock_in_latitude',
        'clock_in_longitude',
        'clock_in_location',
        'clock_out_latitude',
        'clock_out_longitude',
        'clock_out_location',
        'clock_in_photo',
        'clock_out_photo',
        'face_confidence',
        'clock_in_notes',
        'clock_out_notes',
        'notes',
        'meta_data',
    ];

    /**
     * The attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'clock_in' => 'datetime',
            'clock_out' => 'datetime',
            'work_hours' => 'decimal:2',
            'clock_in_latitude' => 'decimal:8',
            'clock_in_longitude' => 'decimal:8',
            'clock_out_latitude' => 'decimal:8',
            'clock_out_longitude' => 'decimal:8',
            'face_confidence' => 'decimal:2',
            'meta_data' => 'array',
        ];
    }

    /**
     * Get the user that owns the attendance
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate work hours when clock_out is set
     */
    public function calculateWorkHours(): float
    {
        if (!$this->clock_in || !$this->clock_out) {
            return 0;
        }

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        return $clockOut->diffInHours($clockIn, true);
    }

    /**
     * Determine attendance status based on work rules
     */
    public function determineStatus(): string
    {
        $workStart = config('attendance.work_start_time', '08:00');
        $workEnd = config('attendance.work_end_time', '17:00');
        $lateThreshold = config('attendance.late_threshold_minutes', 15);

        if (!$this->clock_in) {
            return 'absent';
        }

        $clockIn = Carbon::parse($this->clock_in);
        $workStartTime = Carbon::parse($workStart);

        // Check if late
        if ($clockIn->isAfter($workStartTime->addMinutes($lateThreshold))) {
            return 'late';
        }

        // Check if partial day (early checkout)
        if ($this->clock_out) {
            $clockOut = Carbon::parse($this->clock_out);
            $workEndTime = Carbon::parse($workEnd);
            $earlyThreshold = config('attendance.early_checkout_threshold_minutes', 30);

            if ($clockOut->isBefore($workEndTime->subMinutes($earlyThreshold))) {
                return 'partial';
            }
        }

        return 'present';
    }

    /**
     * Check if attendance is within office radius
     */
    public function isWithinOfficeRadius(?float $latitude = null, ?float $longitude = null): bool
    {
        $officeLat = config('attendance.office_latitude', -6.2088);
        $officeLon = config('attendance.office_longitude', 106.8456);
        $officeRadius = config('attendance.office_radius', 100); // meters

        $lat = $latitude ?? $this->clock_in_latitude;
        $lon = $longitude ?? $this->clock_in_longitude;

        if (!$lat || !$lon) {
            return false;
        }

        $distance = $this->calculateDistance($lat, $lon, $officeLat, $officeLon);
        return $distance <= $officeRadius;
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get photo URL for clock in
     */
    public function getPhotoInUrlAttribute(): ?string
    {
        if ($this->clock_in_photo) {
            return asset('storage/attendance/' . $this->clock_in_photo);
        }
        return null;
    }

    /**
     * Get photo URL for clock out
     */
    public function getPhotoOutUrlAttribute(): ?string
    {
        if ($this->clock_out_photo) {
            return asset('storage/attendance/' . $this->clock_out_photo);
        }
        return null;
    }

    /**
     * Scope for today's attendances
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    /**
     * Scope for this month's attendances
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', now()->year)
            ->whereMonth('date', now()->month);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($attendance) {
            // Auto-calculate work hours if both clock_in and clock_out are set
            if ($attendance->clock_in && $attendance->clock_out) {
                $attendance->work_hours = $attendance->calculateWorkHours();
            }

            // Auto-determine status
            $attendance->status = $attendance->determineStatus();
        });
    }
}
