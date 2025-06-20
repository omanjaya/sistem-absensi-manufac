<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\LeaveController;
use App\Http\Controllers\API\SalaryController;
use App\Http\Controllers\API\DashboardController;
use App\Http\Controllers\API\FaceRecognitionController;
use App\Http\Controllers\API\ScheduleController;
use App\Http\Controllers\API\HolidayController;
use App\Http\Controllers\API\WorkPeriodController;
use App\Http\Controllers\API\NotificationController;
use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\SecurityController;
use App\Http\Controllers\API\MobileController;
use App\Http\Controllers\API\ProjectController;
use App\Http\Controllers\API\TimeEntryController;
use App\Http\Controllers\API\WorkflowController;
use App\Http\Controllers\API\SettingsController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned the "api" middleware group. Make something great!
|
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Laravel backend is running',
        'timestamp' => now()->toISOString(),
        'port' => request()->getPort(),
        'url' => request()->getSchemeAndHttpHost()
    ]);
});

// Test endpoint (temporarily outside auth middleware for debugging)
Route::get('/test-notifications', function () {
    try {
        // Test without authentication for debugging
        $userCount = \App\Models\User::count();
        $notificationCount = \App\Models\Notification::count();

        return response()->json([
            'success' => true,
            'user_count' => $userCount,
            'notification_count' => $notificationCount,
            'message' => 'Notification system test successful',
            'database_connected' => true,
            'server_port' => request()->getPort(),
            'server_url' => request()->getSchemeAndHttpHost()
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// Health check endpoint untuk auto-detection
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Laravel backend is running',
        'timestamp' => now()->toISOString(),
        'port' => request()->getPort(),
        'url' => request()->getSchemeAndHttpHost()
    ]);
});

// Debug CORS dan connectivity
Route::get('/debug-cors', function () {
    return response()->json([
        'success' => true,
        'message' => 'CORS working properly',
        'timestamp' => now()->toISOString(),
        'headers' => [
            'origin' => request()->header('Origin'),
            'user-agent' => request()->header('User-Agent'),
            'referer' => request()->header('Referer'),
        ],
        'server_info' => [
            'port' => request()->getPort(),
            'url' => request()->getSchemeAndHttpHost(),
            'method' => request()->method(),
            'path' => request()->path(),
        ]
    ]);
});

// Test login endpoint tanpa auth untuk debugging
Route::post('/debug-login', function () {
    $credentials = request()->only(['email', 'password']);

    try {
        $user = \App\Models\User::where('email', $credentials['email'])->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'email' => $credentials['email']
            ], 404);
        }

        $passwordCheck = \Illuminate\Support\Facades\Hash::check($credentials['password'], $user->password);

        return response()->json([
            'success' => true,
            'message' => 'Debug login test',
            'user_found' => true,
            'password_correct' => $passwordCheck,
            'user_data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
});

// 🔔 TEMPORARY: Notification endpoints without auth for debugging
Route::prefix('notifications')->group(function () {
    Route::get('/debug', function () {
        try {
            // Get first user for testing
            $user = \App\Models\User::first();
            if (!$user) {
                return response()->json(['error' => 'No users found'], 404);
            }

            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'user_name' => $user->name,
                'notifications_count' => $user->notifications()->count(),
                'message' => 'Debug endpoint working',
                'server_info' => [
                    'port' => request()->getPort(),
                    'url' => request()->getSchemeAndHttpHost()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    Route::get('/create-test', function () {
        try {
            $user = \App\Models\User::first();
            if (!$user) {
                return response()->json(['error' => 'No users found'], 404);
            }

            // Create test notification
            $notification = \App\Models\Notification::create([
                'user_id' => $user->id,
                'title' => 'Test Notification - Port ' . request()->getPort(),
                'message' => 'This is a test notification for debugging from port ' . request()->getPort(),
                'type' => 'system_maintenance',
                'priority' => 'medium',
                'channel' => 'app',
                'status' => 'sent',
                'sent_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'notification' => $notification,
                'message' => 'Test notification created',
                'server_info' => [
                    'port' => request()->getPort(),
                    'url' => request()->getSchemeAndHttpHost()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
});

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });

    // Dashboard routes
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-attendances', [DashboardController::class, 'recentAttendances']);

    // Attendance routes
    Route::prefix('attendances')->group(function () {
        Route::get('/', [AttendanceController::class, 'index']);
        Route::post('/', [AttendanceController::class, 'store']);
        Route::get('/today', [AttendanceController::class, 'today']);
        Route::get('/export', [AttendanceController::class, 'export']);
        Route::get('/{attendance}', [AttendanceController::class, 'show']);
        Route::put('/{attendance}', [AttendanceController::class, 'update'])->middleware('role:admin');
        Route::delete('/{attendance}', [AttendanceController::class, 'destroy'])->middleware('role:admin');
    });

    // Leave routes
    Route::prefix('leaves')->group(function () {
        Route::get('/', [LeaveController::class, 'index']);
        Route::post('/', [LeaveController::class, 'store']);
        Route::get('/{leave}', [LeaveController::class, 'show']);
        Route::put('/{leave}', [LeaveController::class, 'update']);
        Route::delete('/{leave}', [LeaveController::class, 'destroy']);

        // Admin-only leave management
        Route::middleware('role:admin')->group(function () {
            Route::post('/{leave}/approve', [LeaveController::class, 'approve']);
            Route::post('/{leave}/reject', [LeaveController::class, 'reject']);
        });
    });

    // Face recognition routes
    Route::prefix('face')->group(function () {
        Route::post('/register', [FaceRecognitionController::class, 'register']);
        Route::post('/recognize', [FaceRecognitionController::class, 'recognize']);
        Route::get('/status/{user}', [FaceRecognitionController::class, 'status']);
    });

    // Admin-only routes
    Route::middleware('role:admin')->group(function () {

        // User/Employee management
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{user}', [UserController::class, 'show']);
            Route::put('/{user}', [UserController::class, 'update']);
            Route::delete('/{user}', [UserController::class, 'destroy']);

            // Import Template Features
            Route::get('/template/download', [UserController::class, 'downloadTemplate'])->middleware('throttle:10,1'); // 10 downloads per minute
            Route::post('/import/preview', [UserController::class, 'previewImport'])->middleware('throttle:5,1'); // 5 previews per minute
            Route::post('/import/execute', [UserController::class, 'executeImport'])->middleware('throttle:2,1'); // 2 imports per minute
        });

        // Alias for employees (same as users but different naming)
        Route::prefix('employees')->group(function () {
            Route::get('/', [UserController::class, 'index']);
            Route::post('/', [UserController::class, 'store']);
            Route::get('/{user}', [UserController::class, 'show']);
            Route::put('/{user}', [UserController::class, 'update']);
            Route::delete('/{user}', [UserController::class, 'destroy']);

            // Import Template Features for employees endpoint
            Route::get('/template/download', [UserController::class, 'downloadTemplate'])->middleware('throttle:10,1'); // 10 downloads per minute
            Route::post('/import/preview', [UserController::class, 'previewImport'])->middleware('throttle:5,1'); // 5 previews per minute
            Route::post('/import/execute', [UserController::class, 'executeImport'])->middleware('throttle:2,1'); // 2 imports per minute
        });

        // Salary management
        Route::prefix('salaries')->group(function () {
            Route::get('/', [SalaryController::class, 'index']);
            Route::post('/generate', [SalaryController::class, 'generate']);
            Route::get('/export', [SalaryController::class, 'export']);
            Route::get('/{salary}', [SalaryController::class, 'show']);
            Route::put('/{salary}', [SalaryController::class, 'update']);
            Route::post('/{salary}/finalize', [SalaryController::class, 'finalize']);
            Route::post('/{salary}/mark-paid', [SalaryController::class, 'markPaid']);
        });

        // Teacher/Employee Schedule management
        Route::prefix('schedules')->group(function () {
            Route::get('/', [ScheduleController::class, 'index'])->middleware('permission:schedule.view');
            Route::post('/', [ScheduleController::class, 'store'])->middleware('permission:schedule.create');
            Route::get('/weekly/{user?}', [ScheduleController::class, 'weekly'])->middleware('permission:schedule.view');
            Route::get('/daily/{date?}', [ScheduleController::class, 'daily'])->middleware('permission:schedule.view');
            Route::get('/conflicts', [ScheduleController::class, 'conflicts'])->middleware('permission:schedule.view');
            Route::get('/{schedule}', [ScheduleController::class, 'show'])->middleware('permission:schedule.view');
            Route::put('/{schedule}', [ScheduleController::class, 'update'])->middleware('permission:schedule.edit');
            Route::delete('/{schedule}', [ScheduleController::class, 'destroy'])->middleware('permission:schedule.delete');
        });

        // School Holiday management
        Route::prefix('holidays')->group(function () {
            Route::get('/', [HolidayController::class, 'index'])->middleware('permission:holiday.view');
            Route::post('/', [HolidayController::class, 'store'])->middleware('permission:holiday.create');
            Route::get('/calendar', [HolidayController::class, 'calendar'])->middleware('permission:holiday.view');
            Route::get('/check', [HolidayController::class, 'isHoliday'])->middleware('permission:holiday.view');
            Route::get('/{holiday}', [HolidayController::class, 'show'])->middleware('permission:holiday.view');
            Route::put('/{holiday}', [HolidayController::class, 'update'])->middleware('permission:holiday.edit');
            Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->middleware('permission:holiday.delete');
        });

        // Work Period management (first hour timing)
        Route::prefix('work-periods')->group(function () {
            Route::get('/', [WorkPeriodController::class, 'index'])->middleware('permission:work_period.view');
            Route::post('/', [WorkPeriodController::class, 'store'])->middleware('permission:work_period.create');
            Route::get('/schedule/{date?}', [WorkPeriodController::class, 'getScheduleForDate'])->middleware('permission:work_period.view');
            Route::get('/{workPeriod}', [WorkPeriodController::class, 'show'])->middleware('permission:work_period.view');
            Route::put('/{workPeriod}', [WorkPeriodController::class, 'update'])->middleware('permission:work_period.edit');
            Route::delete('/{workPeriod}', [WorkPeriodController::class, 'destroy'])->middleware('permission:work_period.delete');
        });

        // Admin reports
        Route::prefix('reports')->group(function () {
            Route::get('/attendance-summary', [DashboardController::class, 'attendanceSummary']);
            Route::get('/monthly-stats', [DashboardController::class, 'monthlyStats']);
            Route::get('/employee-performance', [DashboardController::class, 'employeePerformance']);
        });
    });

    // 🔔 NOTIFICATION SYSTEM ROUTES (All authenticated users)
    Route::prefix('notifications')->group(function () {
        Route::get('/test', function () {
            try {
                $user = Auth::user();
                if (!$user) {
                    return response()->json(['error' => 'Not authenticated'], 401);
                }

                return response()->json([
                    'success' => true,
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'notifications_count' => $user->notifications()->count(),
                    'message' => 'Notification system test successful'
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 500);
            }
        });
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/stats', [NotificationController::class, 'stats']);
        Route::get('/settings', [NotificationController::class, 'settings']);
        Route::get('/stream', [NotificationController::class, 'stream']); // Real-time notification stream
        Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::put('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);

        // Admin-only notification routes
        Route::middleware('role:admin')->group(function () {
            Route::post('/send', [NotificationController::class, 'send']);
            Route::get('/templates', [NotificationController::class, 'templates']);
        });
    });

    // 📊 ANALYTICS & REPORTING ROUTES (All authenticated users - filtered by role)
    Route::prefix('analytics')->group(function () {
        Route::get('/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::get('/attendance-trends', [AnalyticsController::class, 'attendanceTrends']);
        Route::get('/my-performance', [AnalyticsController::class, 'myPerformance']);

        // Admin-only analytics routes
        Route::middleware('role:admin')->group(function () {
            Route::post('/export', [AnalyticsController::class, 'export']);
            Route::get('/productivity-metrics', [AnalyticsController::class, 'productivityMetrics']);
            Route::get('/financial-overview', [AnalyticsController::class, 'financialOverview']);
            Route::get('/predictions', [AnalyticsController::class, 'predictions']);
            Route::get('/department-analytics', [AnalyticsController::class, 'departmentAnalytics']);
        });
    });

    // 🛡️ SECURITY & AUDIT ROUTES
    Route::prefix('security')->group(function () {
        Route::get('/my-login-history', [SecurityController::class, 'myLoginHistory']);
        Route::post('/2fa/enable', [SecurityController::class, 'enable2FA']);
        Route::post('/2fa/verify', [SecurityController::class, 'verify2FA']);
        Route::post('/2fa/disable', [SecurityController::class, 'disable2FA']);

        // Admin-only security routes
        Route::middleware('role:admin')->group(function () {
            Route::get('/audit-logs', [SecurityController::class, 'auditLogs']);
            Route::get('/security-alerts', [SecurityController::class, 'securityAlerts']);
            Route::get('/login-history', [SecurityController::class, 'loginHistory']);
            Route::get('/suspicious-activities', [SecurityController::class, 'suspiciousActivities']);
            Route::get('/device-logs', [SecurityController::class, 'deviceLogs']);
        });
    });

    // 📱 MOBILE PWA ROUTES (All authenticated users)
    Route::prefix('mobile')->group(function () {
        Route::post('/device-token', [MobileController::class, 'registerDevice']);
        Route::put('/device-token', [MobileController::class, 'updateDevice']);
        Route::delete('/device-token', [MobileController::class, 'removeDevice']);
        Route::get('/offline-data', [MobileController::class, 'getOfflineData']);
        Route::post('/sync-attendance', [MobileController::class, 'syncAttendance']);
        Route::get('/device-capabilities', [MobileController::class, 'getDeviceCapabilities']);
        Route::post('/biometric-register', [MobileController::class, 'registerBiometric']);
        Route::post('/qr-scan', [MobileController::class, 'scanQRCode']);
    });

    // 🎯 PROJECT & TIME TRACKING ROUTES
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index']);
        Route::get('/my-projects', [ProjectController::class, 'myProjects']);
        Route::get('/{project}', [ProjectController::class, 'show']);

        // Admin/Manager-only project routes
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [ProjectController::class, 'store']);
            Route::put('/{project}', [ProjectController::class, 'update']);
            Route::delete('/{project}', [ProjectController::class, 'destroy']);
            Route::get('/{project}/analytics', [ProjectController::class, 'analytics']);
        });

        // Time entries for projects
        Route::get('/{project}/time-entries', [TimeEntryController::class, 'projectEntries']);
        Route::post('/{project}/time-entries', [TimeEntryController::class, 'store']);
    });

    Route::prefix('time-entries')->group(function () {
        Route::get('/', [TimeEntryController::class, 'index']);
        Route::post('/', [TimeEntryController::class, 'store']);
        Route::get('/active', [TimeEntryController::class, 'getActive']);
        Route::get('/my-entries', [TimeEntryController::class, 'myEntries']);
        Route::get('/{timeEntry}', [TimeEntryController::class, 'show']);
        Route::put('/{timeEntry}', [TimeEntryController::class, 'update']);
        Route::delete('/{timeEntry}', [TimeEntryController::class, 'destroy']);
        Route::post('/{timeEntry}/start', [TimeEntryController::class, 'start']);
        Route::post('/{timeEntry}/stop', [TimeEntryController::class, 'stop']);
        Route::post('/{timeEntry}/pause', [TimeEntryController::class, 'pause']);
        Route::post('/quick-start', [TimeEntryController::class, 'quickStart']);
    });

    // 🔄 ADVANCED WORKFLOW ROUTES (Admin-only)
    Route::middleware('role:admin')->prefix('workflows')->group(function () {
        Route::post('/bulk-approve-leaves', [WorkflowController::class, 'bulkApproveLeaves']);
        Route::post('/bulk-generate-salaries', [WorkflowController::class, 'bulkGenerateSalaries']);
        Route::post('/schedule-notifications', [WorkflowController::class, 'scheduleNotifications']);
        Route::get('/approval-queue', [WorkflowController::class, 'getApprovalQueue']);
        Route::post('/batch-operations', [WorkflowController::class, 'batchOperations']);
        Route::get('/workflow-status', [WorkflowController::class, 'getWorkflowStatus']);
    });

    // 🎨 CUSTOMIZATION & SETTINGS ROUTES
    Route::prefix('settings')->group(function () {
        Route::get('/user-preferences', [SettingsController::class, 'getUserPreferences']);
        Route::put('/user-preferences', [SettingsController::class, 'updateUserPreferences']);
        Route::get('/themes', [SettingsController::class, 'getAvailableThemes']);
        Route::post('/export-my-data', [SettingsController::class, 'exportMyData']);

        // Admin-only settings routes
        Route::middleware('role:admin')->group(function () {
            Route::get('/system', [SettingsController::class, 'getSystemSettings']);
            Route::put('/system', [SettingsController::class, 'updateSystemSettings']);
            Route::post('/export-all-data', [SettingsController::class, 'exportAllData']);
            Route::get('/backup-status', [SettingsController::class, 'getBackupStatus']);
            Route::post('/create-backup', [SettingsController::class, 'createBackup']);
        });
    });

    // User/Employee Management
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/users', [UserController::class, 'store']);
    Route::get('/users/{id}', [UserController::class, 'show']);
    Route::put('/users/{id}', [UserController::class, 'update']);
    Route::delete('/users/{id}', [UserController::class, 'destroy']);

    // Import Template Features for /users endpoint (remove duplicates from /employees)
    Route::get('/users/template/download', [UserController::class, 'downloadTemplate'])->middleware('throttle:10,1');
    Route::post('/users/import/preview', [UserController::class, 'previewImport'])->middleware('throttle:5,1');
    Route::post('/users/import/execute', [UserController::class, 'executeImport'])->middleware('throttle:2,1');
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found'
    ], 404);
});
