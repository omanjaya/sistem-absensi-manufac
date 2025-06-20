<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'session_id',
        'risk_level',
        'context',
        'location_data',
        'device_fingerprint'
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'context' => 'array',
            'location_data' => 'array',
        ];
    }

    /**
     * Get the user who performed the action
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model
     */
    public function auditable()
    {
        return $this->morphTo('model');
    }

    /**
     * Log an action
     */
    public static function logAction($action, $model = null, $oldValues = [], $newValues = [], $context = [])
    {
        $user = Auth::user();
        $request = request();

        return static::create([
            'user_id' => $user?->id,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id' => $request->session()?->getId(),
            'risk_level' => static::calculateRiskLevel($action, $context),
            'context' => $context,
            'location_data' => static::getLocationData($request),
            'device_fingerprint' => static::generateDeviceFingerprint($request)
        ]);
    }

    /**
     * Calculate risk level based on action and context
     */
    protected static function calculateRiskLevel($action, $context = [])
    {
        $highRiskActions = [
            'user.deleted',
            'salary.modified',
            'admin.login',
            'permission.changed',
            'data.exported',
            'face.registered',
            'schedule.mass_update'
        ];

        $mediumRiskActions = [
            'user.updated',
            'attendance.modified',
            'leave.approved',
            'holiday.created'
        ];

        if (in_array($action, $highRiskActions)) {
            return 'high';
        }

        if (in_array($action, $mediumRiskActions)) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get location data from request
     */
    protected static function getLocationData($request)
    {
        // In production, integrate with IP geolocation service
        return [
            'ip' => $request->ip(),
            'country' => 'ID', // Default to Indonesia
            'city' => 'Unknown',
            'timezone' => config('app.timezone')
        ];
    }

    /**
     * Generate device fingerprint
     */
    protected static function generateDeviceFingerprint($request)
    {
        $components = [
            $request->userAgent(),
            $request->header('Accept-Language'),
            $request->header('Accept-Encoding'),
            $request->ip()
        ];

        return hash('sha256', implode('|', array_filter($components)));
    }

    /**
     * Scope for high risk activities
     */
    public function scopeHighRisk($query)
    {
        return $query->where('risk_level', 'high');
    }

    /**
     * Scope for suspicious activities
     */
    public function scopeSuspicious($query)
    {
        return $query->where(function ($q) {
            $q->where('risk_level', 'high')
                ->orWhere(function ($subQ) {
                    $subQ->where('action', 'like', '%login%')
                        ->where('created_at', '>', now()->subHours(24));
                });
        });
    }

    /**
     * Scope for user activities
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, $start, $end)
    {
        return $query->whereBetween('created_at', [$start, $end]);
    }
}
