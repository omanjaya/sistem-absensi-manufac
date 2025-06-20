<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_type',
        'device_name',
        'push_token',
        'device_id',
        'app_version',
        'os_version',
        'capabilities',
        'preferences',
        'is_active',
        'last_used_at'
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'preferences' => 'array',
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Get the user that owns the device token
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active devices
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific device type
     */
    public function scopeDeviceType($query, $type)
    {
        return $query->where('device_type', $type);
    }
}
