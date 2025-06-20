<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Attendance;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MobileController extends Controller
{
    /**
     * Register device token for push notifications
     */
    public function registerDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_type' => 'required|in:ios,android,web,desktop',
            'device_name' => 'nullable|string|max:255',
            'push_token' => 'nullable|string',
            'device_id' => 'required|string|max:255',
            'app_version' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
            'capabilities' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Check if device already exists
        $existingDevice = DeviceToken::where('user_id', $user->id)
            ->where('device_id', $request->device_id)
            ->first();

        if ($existingDevice) {
            // Update existing device
            $existingDevice->update([
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'push_token' => $request->push_token,
                'app_version' => $request->app_version,
                'os_version' => $request->os_version,
                'capabilities' => $request->capabilities,
                'is_active' => true,
                'last_used_at' => now()
            ]);

            $device = $existingDevice;
        } else {
            // Create new device
            $device = DeviceToken::create([
                'user_id' => $user->id,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'push_token' => $request->push_token,
                'device_id' => $request->device_id,
                'app_version' => $request->app_version,
                'os_version' => $request->os_version,
                'capabilities' => $request->capabilities ?? [],
                'preferences' => $this->getDefaultPreferences(),
                'is_active' => true,
                'last_used_at' => now()
            ]);
        }

        AuditLog::logAction('device.registered', $device, [], $device->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'data' => $device
        ]);
    }

    /**
     * Update device information
     */
    public function updateDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'preferences' => 'nullable|array',
            'push_token' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $device = DeviceToken::where('user_id', $user->id)
            ->where('device_id', $request->device_id)
            ->firstOrFail();

        $oldData = $device->toArray();

        $device->update([
            'preferences' => $request->preferences ?? $device->preferences,
            'push_token' => $request->push_token ?? $device->push_token,
            'last_used_at' => now()
        ]);

        AuditLog::logAction('device.updated', $device, $oldData, $device->toArray());

        return response()->json([
            'success' => true,
            'message' => 'Device updated successfully',
            'data' => $device
        ]);
    }

    /**
     * Remove device token
     */
    public function removeDevice(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $device = DeviceToken::where('user_id', $user->id)
            ->where('device_id', $request->device_id)
            ->firstOrFail();

        AuditLog::logAction('device.removed', $device, $device->toArray(), []);

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device removed successfully'
        ]);
    }

    /**
     * Get offline data for PWA functionality
     */
    public function getOfflineData(Request $request)
    {
        $user = Auth::user();

        // Get essential data for offline use
        $offlineData = [
            'user_profile' => $user->only(['id', 'name', 'email', 'employee_id', 'department', 'position']),
            'recent_attendances' => $user->attendances()
                ->with(['user:id,name'])
                ->whereBetween('date', [now()->subWeeks(2), now()])
                ->orderBy('date', 'desc')
                ->limit(20)
                ->get(),
            'current_schedule' => $this->getCurrentSchedule($user),
            'pending_leaves' => $user->leaves()
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
            'system_settings' => [
                'work_hours' => [
                    'start' => '09:00',
                    'end' => '17:00'
                ],
                'late_threshold' => 15, // minutes
                'timezone' => config('app.timezone')
            ],
            'offline_capabilities' => [
                'can_check_in' => true,
                'can_check_out' => true,
                'can_view_schedule' => true,
                'can_submit_leave' => false, // Requires online
                'can_sync_later' => true
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $offlineData,
            'sync_timestamp' => now(),
            'cache_duration' => 86400 // 24 hours in seconds
        ]);
    }

    /**
     * Sync attendance data from offline mode
     */
    public function syncAttendance(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'attendances' => 'required|array',
            'attendances.*.date' => 'required|date',
            'attendances.*.clock_in' => 'nullable|date_format:H:i:s',
            'attendances.*.clock_out' => 'nullable|date_format:H:i:s',
            'attendances.*.location' => 'nullable|array',
            'attendances.*.notes' => 'nullable|string',
            'attendances.*.offline_timestamp' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $syncedAttendances = [];
        $errors = [];

        foreach ($request->attendances as $attendanceData) {
            try {
                // Check if attendance already exists for this date
                $existingAttendance = Attendance::where('user_id', $user->id)
                    ->whereDate('date', $attendanceData['date'])
                    ->first();

                if ($existingAttendance) {
                    // Update existing attendance if offline data is newer
                    if ($attendanceData['offline_timestamp'] > $existingAttendance->updated_at->timestamp) {
                        $existingAttendance->update([
                            'clock_in' => $attendanceData['clock_in'],
                            'clock_out' => $attendanceData['clock_out'],
                            'notes' => $attendanceData['notes'] ?? $existingAttendance->notes,
                            'location' => $attendanceData['location'] ?? $existingAttendance->location,
                            'status' => $this->calculateAttendanceStatus($attendanceData),
                            'work_hours' => $this->calculateWorkHours($attendanceData['clock_in'], $attendanceData['clock_out'])
                        ]);

                        $syncedAttendances[] = $existingAttendance;

                        AuditLog::logAction('attendance.synced', $existingAttendance, [], [], [
                            'source' => 'offline_sync'
                        ]);
                    }
                } else {
                    // Create new attendance
                    $attendance = Attendance::create([
                        'user_id' => $user->id,
                        'date' => $attendanceData['date'],
                        'clock_in' => $attendanceData['clock_in'],
                        'clock_out' => $attendanceData['clock_out'],
                        'notes' => $attendanceData['notes'],
                        'location' => $attendanceData['location'],
                        'status' => $this->calculateAttendanceStatus($attendanceData),
                        'work_hours' => $this->calculateWorkHours($attendanceData['clock_in'], $attendanceData['clock_out']),
                        'overtime_hours' => 0
                    ]);

                    $syncedAttendances[] = $attendance;

                    AuditLog::logAction('attendance.created_offline', $attendance, [], $attendance->toArray(), [
                        'source' => 'offline_sync'
                    ]);
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'date' => $attendanceData['date'],
                    'error' => 'Failed to sync attendance: ' . $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Attendance data synced successfully',
            'data' => [
                'synced_count' => count($syncedAttendances),
                'error_count' => count($errors),
                'synced_attendances' => $syncedAttendances,
                'errors' => $errors
            ]
        ]);
    }

    /**
     * Get device capabilities
     */
    public function getDeviceCapabilities(Request $request)
    {
        $userAgent = $request->userAgent();
        $capabilities = [
            'push_notifications' => $this->supportsPushNotifications($userAgent),
            'geolocation' => true, // Assume most modern devices support this
            'camera' => $this->supportsCamera($userAgent),
            'biometric_auth' => $this->supportsBiometricAuth($userAgent),
            'offline_storage' => true, // Modern browsers support localStorage/IndexedDB
            'background_sync' => $this->supportsBackgroundSync($userAgent),
            'qr_scanner' => $this->supportsCamera($userAgent),
            'nfc' => $this->supportsNFC($userAgent)
        ];

        return response()->json([
            'success' => true,
            'data' => $capabilities
        ]);
    }

    /**
     * Register biometric authentication
     */
    public function registerBiometric(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_id' => 'required|string',
            'biometric_type' => 'required|in:fingerprint,face,voice',
            'public_key' => 'required|string',
            'challenge' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $device = DeviceToken::where('user_id', $user->id)
            ->where('device_id', $request->device_id)
            ->firstOrFail();

        // Store biometric data (in production, use proper encryption)
        $biometricData = [
            'type' => $request->biometric_type,
            'public_key' => $request->public_key,
            'registered_at' => now(),
            'challenge' => hash('sha256', $request->challenge)
        ];

        $capabilities = $device->capabilities ?? [];
        $capabilities['biometric'] = $biometricData;

        $device->update(['capabilities' => $capabilities]);

        AuditLog::logAction('biometric.registered', $device, [], [
            'biometric_type' => $request->biometric_type
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Biometric authentication registered successfully'
        ]);
    }

    /**
     * Scan QR code for attendance
     */
    public function scanQRCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'qr_data' => 'required|string',
            'location' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Decode QR data (in production, implement proper QR validation)
        $qrData = json_decode($request->qr_data, true);

        if (!$qrData || !isset($qrData['location_id']) || !isset($qrData['timestamp'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code'
            ], 422);
        }

        // Check if QR code is not expired (valid for 5 minutes)
        if (time() - $qrData['timestamp'] > 300) {
            return response()->json([
                'success' => false,
                'message' => 'QR code has expired'
            ], 422);
        }

        $user = Auth::user();

        // Process attendance based on QR scan
        $today = today();
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if (!$attendance) {
            // Clock in
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $today,
                'clock_in' => now()->format('H:i:s'),
                'status' => now()->format('H:i') <= '09:15' ? 'present' : 'late',
                'location' => $request->location,
                'notes' => 'Clocked in via QR scan at location: ' . ($qrData['location_name'] ?? 'Unknown')
            ]);

            AuditLog::logAction('attendance.qr_clock_in', $attendance, [], $attendance->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Successfully clocked in',
                'data' => $attendance
            ]);
        } else if (!$attendance->clock_out) {
            // Clock out
            $clockOut = now()->format('H:i:s');
            $workHours = $this->calculateWorkHours($attendance->clock_in, $clockOut);

            $attendance->update([
                'clock_out' => $clockOut,
                'work_hours' => $workHours,
                'notes' => ($attendance->notes ?? '') . ' | Clocked out via QR scan'
            ]);

            AuditLog::logAction('attendance.qr_clock_out', $attendance, [], $attendance->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Successfully clocked out',
                'data' => $attendance
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'You have already completed attendance for today'
            ], 422);
        }
    }

    /**
     * Get default device preferences
     */
    private function getDefaultPreferences()
    {
        return [
            'notifications' => [
                'attendance_reminders' => true,
                'leave_updates' => true,
                'schedule_changes' => true,
                'sound_enabled' => true,
                'vibration_enabled' => true
            ],
            'security' => [
                'biometric_required' => false,
                'auto_logout_minutes' => 30
            ],
            'display' => [
                'theme' => 'auto',
                'language' => 'en'
            ]
        ];
    }

    /**
     * Get current schedule for user
     */
    private function getCurrentSchedule($user)
    {
        // Simple schedule (in production, integrate with Schedule model)
        return [
            'monday' => ['start' => '09:00', 'end' => '17:00'],
            'tuesday' => ['start' => '09:00', 'end' => '17:00'],
            'wednesday' => ['start' => '09:00', 'end' => '17:00'],
            'thursday' => ['start' => '09:00', 'end' => '17:00'],
            'friday' => ['start' => '09:00', 'end' => '17:00'],
            'saturday' => null,
            'sunday' => null
        ];
    }

    /**
     * Calculate attendance status
     */
    private function calculateAttendanceStatus($attendanceData)
    {
        if (!$attendanceData['clock_in']) {
            return 'absent';
        }

        $clockIn = Carbon::createFromFormat('H:i:s', $attendanceData['clock_in']);
        $workStart = Carbon::createFromFormat('H:i', '09:00');

        return $clockIn->gt($workStart->addMinutes(15)) ? 'late' : 'present';
    }

    /**
     * Calculate work hours
     */
    private function calculateWorkHours($clockIn, $clockOut)
    {
        if (!$clockIn || !$clockOut) {
            return 0;
        }

        $start = Carbon::createFromFormat('H:i:s', $clockIn);
        $end = Carbon::createFromFormat('H:i:s', $clockOut);

        return round($start->diffInMinutes($end) / 60, 2);
    }

    /**
     * Check if device supports push notifications
     */
    private function supportsPushNotifications($userAgent)
    {
        return strpos($userAgent, 'Chrome') !== false ||
            strpos($userAgent, 'Firefox') !== false ||
            strpos($userAgent, 'Safari') !== false;
    }

    /**
     * Check if device supports camera
     */
    private function supportsCamera($userAgent)
    {
        return strpos($userAgent, 'Mobile') !== false ||
            strpos($userAgent, 'Chrome') !== false ||
            strpos($userAgent, 'Firefox') !== false;
    }

    /**
     * Check if device supports biometric auth
     */
    private function supportsBiometricAuth($userAgent)
    {
        return strpos($userAgent, 'Mobile') !== false;
    }

    /**
     * Check if device supports background sync
     */
    private function supportsBackgroundSync($userAgent)
    {
        return strpos($userAgent, 'Chrome') !== false;
    }

    /**
     * Check if device supports NFC
     */
    private function supportsNFC($userAgent)
    {
        return strpos($userAgent, 'Android') !== false;
    }
}
