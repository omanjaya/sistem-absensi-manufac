<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    /**
     * Get system settings (Admin only)
     */
    public function getSystemSettings()
    {
        $settings = [
            'general' => [
                'app_name' => config('app.name'),
                'timezone' => config('app.timezone'),
                'locale' => config('app.locale'),
                'maintenance_mode' => config('app.maintenance', false)
            ],
            'attendance' => [
                'work_start_time' => '09:00',
                'work_end_time' => '17:00',
                'late_threshold_minutes' => 15,
                'overtime_threshold_hours' => 8,
                'weekend_work_allowed' => false,
                'geolocation_required' => true,
                'face_recognition_required' => false
            ],
            'notifications' => [
                'email_enabled' => true,
                'sms_enabled' => false,
                'push_enabled' => true,
                'attendance_reminders' => true,
                'daily_reports' => true,
                'weekly_summaries' => true
            ],
            'security' => [
                'session_timeout_minutes' => 30,
                'password_expiry_days' => 90,
                'max_login_attempts' => 5,
                'two_factor_required' => false,
                'ip_whitelist_enabled' => false,
                'audit_log_retention_days' => 365
            ],
            'payroll' => [
                'currency' => 'IDR',
                'pay_frequency' => 'monthly',
                'overtime_rate_multiplier' => 1.5,
                'holiday_rate_multiplier' => 2.0,
                'auto_salary_generation' => false,
                'tax_calculation_enabled' => true
            ],
            'integration' => [
                'face_server_url' => config('services.face_server.url', 'http://localhost:5000'),
                'email_service' => config('mail.default'),
                'backup_storage' => config('filesystems.default'),
                'api_rate_limit' => 60
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $settings
        ]);
    }

    /**
     * Update system settings (Admin only)
     */
    public function updateSystemSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category' => 'required|in:general,attendance,notifications,security,payroll,integration',
            'settings' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $category = $request->category;
        $settings = $request->settings;

        // Validate category-specific settings
        $validationRules = $this->getValidationRules($category);

        if ($validationRules) {
            $settingsValidator = Validator::make($settings, $validationRules);

            if ($settingsValidator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Settings validation failed',
                    'errors' => $settingsValidator->errors()
                ], 422);
            }
        }

        // Store settings (in production, use proper config storage)
        $this->storeSystemSettings($category, $settings);

        AuditLog::logAction('system_settings.updated', null, [], [
            'category' => $category,
            'settings' => $settings
        ]);

        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully'
        ]);
    }

    /**
     * Get user preferences
     */
    public function getUserPreferences()
    {
        $user = Auth::user();

        // Default preferences if none exist
        $defaultPreferences = [
            'theme' => 'light',
            'language' => 'en',
            'timezone' => config('app.timezone'),
            'date_format' => 'Y-m-d',
            'time_format' => '24h',
            'dashboard_layout' => 'default',
            'notifications' => [
                'email_attendance_reminder' => true,
                'email_leave_updates' => true,
                'email_salary_notifications' => true,
                'push_attendance_reminder' => true,
                'push_schedule_changes' => true,
                'push_general_announcements' => false
            ],
            'privacy' => [
                'show_profile_to_colleagues' => true,
                'allow_location_tracking' => true,
                'share_attendance_stats' => false
            ],
            'display' => [
                'items_per_page' => 15,
                'show_weekend_in_calendar' => true,
                'default_view' => 'dashboard',
                'compact_mode' => false
            ]
        ];

        // In production, retrieve from user preferences table or user metadata
        $userPreferences = $user->preferences ?? $defaultPreferences;

        return response()->json([
            'success' => true,
            'data' => array_merge($defaultPreferences, $userPreferences)
        ]);
    }

    /**
     * Update user preferences
     */
    public function updateUserPreferences(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'theme' => 'in:light,dark,auto',
            'language' => 'in:en,id',
            'timezone' => 'string',
            'date_format' => 'string',
            'time_format' => 'in:12h,24h',
            'dashboard_layout' => 'in:default,compact,cards',
            'notifications' => 'array',
            'privacy' => 'array',
            'display' => 'array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $oldPreferences = $user->preferences ?? [];

        // Update user preferences (add preferences field to users table migration if needed)
        $newPreferences = array_merge($oldPreferences, $request->all());

        // In production, store in user preferences table or user metadata field
        // For now, we'll simulate the update

        AuditLog::logAction(
            'user_preferences.updated',
            $user,
            ['preferences' => $oldPreferences],
            ['preferences' => $newPreferences]
        );

        return response()->json([
            'success' => true,
            'message' => 'User preferences updated successfully',
            'data' => $newPreferences
        ]);
    }

    /**
     * Get available themes
     */
    public function getAvailableThemes()
    {
        $themes = [
            [
                'id' => 'light',
                'name' => 'Light Theme',
                'description' => 'Clean and bright interface',
                'preview' => '/images/themes/light-preview.jpg',
                'colors' => [
                    'primary' => '#3B82F6',
                    'secondary' => '#64748B',
                    'background' => '#FFFFFF',
                    'surface' => '#F8FAFC'
                ]
            ],
            [
                'id' => 'dark',
                'name' => 'Dark Theme',
                'description' => 'Easy on the eyes, perfect for low-light environments',
                'preview' => '/images/themes/dark-preview.jpg',
                'colors' => [
                    'primary' => '#60A5FA',
                    'secondary' => '#94A3B8',
                    'background' => '#1E293B',
                    'surface' => '#334155'
                ]
            ],
            [
                'id' => 'auto',
                'name' => 'Auto Theme',
                'description' => 'Automatically switches based on system preference',
                'preview' => '/images/themes/auto-preview.jpg',
                'colors' => [
                    'primary' => '#3B82F6',
                    'secondary' => '#64748B',
                    'background' => 'auto',
                    'surface' => 'auto'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $themes
        ]);
    }

    /**
     * Export user data (GDPR compliance)
     */
    public function exportMyData()
    {
        $user = Auth::user();

        $userData = [
            'profile' => $user->only([
                'name',
                'email',
                'employee_id',
                'phone',
                'department',
                'position',
                'join_date'
            ]),
            'attendances' => $user->attendances()->get(),
            'leaves' => $user->leaves()->get(),
            'salaries' => $user->salaries()->get(),
            'notifications' => $user->notifications()->get(),
            'time_entries' => $user->timeEntries()->with('project')->get(),
            'preferences' => $user->preferences ?? [],
            'export_date' => now(),
            'format_version' => '1.0'
        ];

        AuditLog::logAction('data.exported', $user, [], [
            'type' => 'personal_data',
            'records_count' => [
                'attendances' => $userData['attendances']->count(),
                'leaves' => $userData['leaves']->count(),
                'salaries' => $userData['salaries']->count(),
                'notifications' => $userData['notifications']->count()
            ]
        ]);

        return response()->json([
            'success' => true,
            'data' => $userData,
            'download_info' => [
                'filename' => "user_data_{$user->employee_id}_" . now()->format('Y-m-d') . ".json",
                'size_mb' => round(strlen(json_encode($userData)) / 1024 / 1024, 2)
            ]
        ]);
    }

    /**
     * Export all system data (Admin only)
     */
    public function exportAllData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'data_types' => 'array',
            'date_range' => 'array',
            'format' => 'in:json,csv,excel'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $dataTypes = $request->get('data_types', ['users', 'attendances', 'leaves', 'salaries']);
        $format = $request->get('format', 'json');

        $exportData = [];

        if (in_array('users', $dataTypes)) {
            $exportData['users'] = User::with(['attendances', 'leaves', 'salaries'])->get();
        }

        if (in_array('attendances', $dataTypes)) {
            $exportData['attendances'] = \App\Models\Attendance::with('user')->get();
        }

        if (in_array('leaves', $dataTypes)) {
            $exportData['leaves'] = \App\Models\Leave::with('user')->get();
        }

        if (in_array('salaries', $dataTypes)) {
            $exportData['salaries'] = \App\Models\Salary::with('user')->get();
        }

        AuditLog::logAction('system_data.exported', null, [], [
            'data_types' => $dataTypes,
            'format' => $format,
            'admin_id' => Auth::id()
        ]);

        return response()->json([
            'success' => true,
            'data' => $exportData,
            'export_info' => [
                'format' => $format,
                'data_types' => $dataTypes,
                'exported_at' => now(),
                'filename' => "system_export_" . now()->format('Y-m-d_H-i-s') . ".{$format}"
            ]
        ]);
    }

    /**
     * Get backup status (Admin only)
     */
    public function getBackupStatus()
    {
        // Simulate backup status (in production, integrate with actual backup system)
        $backupStatus = [
            'last_backup' => now()->subHours(6),
            'next_scheduled' => now()->addHours(18),
            'backup_frequency' => 'daily',
            'retention_days' => 30,
            'storage_used_gb' => 2.5,
            'storage_limit_gb' => 100,
            'recent_backups' => [
                [
                    'date' => now()->subHours(6),
                    'size_mb' => 245,
                    'status' => 'completed',
                    'type' => 'automatic'
                ],
                [
                    'date' => now()->subDay(),
                    'size_mb' => 243,
                    'status' => 'completed',
                    'type' => 'automatic'
                ],
                [
                    'date' => now()->subDays(2),
                    'size_mb' => 240,
                    'status' => 'completed',
                    'type' => 'automatic'
                ]
            ],
            'health_status' => 'healthy'
        ];

        return response()->json([
            'success' => true,
            'data' => $backupStatus
        ]);
    }

    /**
     * Create manual backup (Admin only)
     */
    public function createBackup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'include_files' => 'boolean',
            'include_database' => 'boolean',
            'compression' => 'in:none,gzip,zip'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Simulate backup creation (in production, implement actual backup)
        $backupInfo = [
            'backup_id' => 'backup_' . now()->timestamp,
            'started_at' => now(),
            'include_files' => $request->get('include_files', true),
            'include_database' => $request->get('include_database', true),
            'compression' => $request->get('compression', 'gzip'),
            'estimated_size_mb' => 250,
            'estimated_duration_minutes' => 15,
            'status' => 'in_progress'
        ];

        AuditLog::logAction('backup.manual_created', null, [], $backupInfo);

        return response()->json([
            'success' => true,
            'message' => 'Manual backup started successfully',
            'data' => $backupInfo
        ]);
    }

    /**
     * Get validation rules for settings categories
     */
    private function getValidationRules($category)
    {
        return match ($category) {
            'attendance' => [
                'work_start_time' => 'date_format:H:i',
                'work_end_time' => 'date_format:H:i',
                'late_threshold_minutes' => 'integer|min:0|max:60',
                'overtime_threshold_hours' => 'integer|min:1|max:24',
                'weekend_work_allowed' => 'boolean',
                'geolocation_required' => 'boolean',
                'face_recognition_required' => 'boolean'
            ],
            'security' => [
                'session_timeout_minutes' => 'integer|min:5|max:480',
                'password_expiry_days' => 'integer|min:0|max:365',
                'max_login_attempts' => 'integer|min:3|max:20',
                'two_factor_required' => 'boolean',
                'ip_whitelist_enabled' => 'boolean',
                'audit_log_retention_days' => 'integer|min:30|max:2555'
            ],
            default => []
        };
    }

    /**
     * Store system settings
     */
    private function storeSystemSettings($category, $settings)
    {
        // In production, store in database or config files
        // For now, we'll simulate storage
        return true;
    }
}
