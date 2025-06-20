<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class NotificationController extends Controller
{
    /**
     * Get user notifications with pagination and filtering
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $query = $user->notifications()->latest();

            // Apply filters
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            if ($request->filled('unread_only')) {
                $query->whereNull('read_at');
            }

            if ($request->filled('priority')) {
                $query->where('priority', $request->priority);
            }

            $notifications = $query->paginate($request->get('per_page', 15));

            // Log audit action safely
            try {
                AuditLog::logAction('notifications.viewed', null, [], [], [
                    'filter' => $request->only(['type', 'unread_only', 'priority'])
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to log audit action: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'unread_count' => $user->notifications()->whereNull('read_at')->count()
            ]);
        } catch (\Exception $e) {
            Log::error('Notification index error: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications'
            ], 500);
        }
    }

    /**
     * Get notification statistics
     */
    public function stats()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $stats = [
                'total' => $user->notifications()->count(),
                'unread' => $user->notifications()->whereNull('read_at')->count(),
                'today' => $user->notifications()->whereDate('created_at', today())->count(),
                'this_week' => $user->notifications()->whereBetween('created_at', [
                    now()->startOfWeek(),
                    now()->endOfWeek()
                ])->count(),
                'by_type' => $user->notifications()
                    ->selectRaw('type, COUNT(*) as count')
                    ->groupBy('type')
                    ->get()
                    ->pluck('count', 'type'),
                'by_priority' => $user->notifications()
                    ->selectRaw('priority, COUNT(*) as count')
                    ->groupBy('priority')
                    ->get()
                    ->pluck('count', 'priority')
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);
        } catch (\Exception $e) {
            Log::error('Notification stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification statistics'
            ], 500);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id)
    {
        try {
            $user = Auth::user();
            $notification = $user->notifications()->findOrFail($id);

            $oldStatus = $notification->read_at;
            $notification->update(['read_at' => now()]);

            // Log audit action safely
            try {
                AuditLog::logAction(
                    'notification.read',
                    $notification,
                    ['read_at' => $oldStatus],
                    ['read_at' => $notification->read_at]
                );
            } catch (\Exception $e) {
                Log::warning('Failed to log audit action: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read'
            ]);
        } catch (\Exception $e) {
            Log::error('Mark notification as read error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark notification as read'
            ], 500);
        }
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead()
    {
        try {
            $user = Auth::user();
            $unreadCount = $user->notifications()->whereNull('read_at')->count();

            $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);

            // Log audit action safely
            try {
                AuditLog::logAction('notifications.mark_all_read', null, [], [], [
                    'marked_count' => $unreadCount
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to log audit action: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => "Marked {$unreadCount} notifications as read"
            ]);
        } catch (\Exception $e) {
            Log::error('Mark all notifications as read error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read'
            ], 500);
        }
    }

    /**
     * Delete notification
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $notification = $user->notifications()->findOrFail($id);

            // Log audit action safely before deletion
            try {
                AuditLog::logAction(
                    'notification.deleted',
                    $notification,
                    $notification->toArray(),
                    []
                );
            } catch (\Exception $e) {
                Log::warning('Failed to log audit action: ' . $e->getMessage());
            }

            $notification->delete();

            return response()->json([
                'success' => true,
                'message' => 'Notification deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Delete notification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete notification'
            ], 500);
        }
    }

    /**
     * Send notification to user/users (Admin only)
     */
    public function send(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_ids' => 'required|array',
                'user_ids.*' => 'exists:users,id',
                'title' => 'required|string|max:255',
                'message' => 'required|string',
                'type' => 'required|in:attendance_reminder,leave_approved,leave_rejected,overtime_alert,salary_generated,schedule_changed,holiday_announced,system_maintenance,security_alert,birthday_reminder,performance_review,training_reminder',
                'priority' => 'in:low,medium,high,urgent',
                'channel' => 'in:app,email,sms,push,whatsapp',
                'scheduled_at' => 'nullable|date|after:now',
                'expires_at' => 'nullable|date|after:now',
                'action_url' => 'nullable|url'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $notifications = [];
            foreach ($request->user_ids as $userId) {
                $notification = Notification::create([
                    'user_id' => $userId,
                    'title' => $request->title,
                    'message' => $request->message,
                    'type' => $request->type,
                    'priority' => $request->get('priority', 'medium'),
                    'channel' => $request->get('channel', 'app'),
                    'scheduled_at' => $request->scheduled_at,
                    'expires_at' => $request->expires_at,
                    'action_url' => $request->action_url,
                    'status' => $request->scheduled_at ? 'pending' : 'sent',
                    'sent_at' => $request->scheduled_at ? null : now()
                ]);

                $notifications[] = $notification;

                // Send real-time notification if not scheduled
                if (!$request->scheduled_at) {
                    $this->sendRealTimeNotification($notification);
                }
            }

            try {
                AuditLog::logAction('notifications.bulk_sent', null, [], [], [
                    'recipient_count' => count($request->user_ids),
                    'type' => $request->type,
                    'scheduled' => !is_null($request->scheduled_at)
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to log audit action: ' . $e->getMessage());
            }

            return response()->json([
                'success' => true,
                'message' => 'Notifications sent successfully',
                'data' => ['count' => count($notifications)]
            ]);
        } catch (\Exception $e) {
            Log::error('Send notification error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send notifications'
            ], 500);
        }
    }

    /**
     * Send real-time notification
     */
    private function sendRealTimeNotification($notification)
    {
        try {
            // In production, integrate with:
            // - Firebase Cloud Messaging for push notifications
            // - WebSocket/Pusher for real-time updates
            // - Email service for email notifications
            // - SMS service for SMS notifications
            // - WhatsApp Business API for WhatsApp notifications

            // For now, we'll simulate the sending
            $notification->update(['sent_at' => now(), 'status' => 'sent']);
        } catch (\Exception $e) {
            Log::error('Send real-time notification error: ' . $e->getMessage());
        }
    }

    /**
     * Get notification templates (Admin only)
     */
    public function templates()
    {
        try {
            $templates = [
                'attendance_reminder' => [
                    'title' => 'Reminder: Clock In Required',
                    'message' => 'Don\'t forget to clock in for today. Your attendance is important!',
                    'priority' => 'medium'
                ],
                'leave_approved' => [
                    'title' => 'Leave Request Approved',
                    'message' => 'Your leave request has been approved. Enjoy your time off!',
                    'priority' => 'high'
                ],
                'leave_rejected' => [
                    'title' => 'Leave Request Rejected',
                    'message' => 'Unfortunately, your leave request has been rejected. Please contact HR for more details.',
                    'priority' => 'high'
                ],
                'overtime_alert' => [
                    'title' => 'Overtime Alert',
                    'message' => 'You have been working overtime. Please ensure you take adequate rest.',
                    'priority' => 'medium'
                ],
                'salary_generated' => [
                    'title' => 'Salary Generated',
                    'message' => 'Your salary for this period has been processed and is ready for review.',
                    'priority' => 'high'
                ],
                'schedule_changed' => [
                    'title' => 'Schedule Updated',
                    'message' => 'Your work schedule has been updated. Please check the latest schedule.',
                    'priority' => 'high'
                ],
                'birthday_reminder' => [
                    'title' => 'Happy Birthday! 🎉',
                    'message' => 'Wishing you a wonderful birthday and a fantastic year ahead!',
                    'priority' => 'low'
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $templates
            ]);
        } catch (\Exception $e) {
            Log::error('Get notification templates error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification templates'
            ], 500);
        }
    }

    /**
     * Get system notification settings
     */
    public function settings()
    {
        try {
            $user = Auth::user();

            // Default notification preferences
            $settings = [
                'attendance_reminder' => ['app' => true, 'email' => true, 'push' => true],
                'leave_approved' => ['app' => true, 'email' => true, 'push' => true],
                'leave_rejected' => ['app' => true, 'email' => true, 'push' => true],
                'overtime_alert' => ['app' => true, 'email' => false, 'push' => true],
                'salary_generated' => ['app' => true, 'email' => true, 'push' => true],
                'schedule_changed' => ['app' => true, 'email' => true, 'push' => true],
                'security_alert' => ['app' => true, 'email' => true, 'push' => true],
                'birthday_reminder' => ['app' => true, 'email' => false, 'push' => false]
            ];

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Get notification settings error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notification settings'
            ], 500);
        }
    }

    /**
     * Real-time notification stream endpoint (Server-Sent Events)
     */
    public function stream(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Set headers for Server-Sent Events
            $headers = [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no', // Disable nginx buffering
            ];

            return response()->stream(function () use ($user) {
                try {
                    // Send connection established event
                    echo "data: " . json_encode([
                        'type' => 'connection',
                        'message' => 'Connected to notification stream',
                        'timestamp' => now()->toISOString()
                    ]) . "\n\n";

                    if (ob_get_level()) {
                        ob_end_flush();
                    }
                    flush();

                    // Keep connection alive and check for new notifications
                    $lastCheck = now();
                    $checkInterval = 5; // Check every 5 seconds
                    $maxDuration = 300; // Keep connection for max 5 minutes
                    $startTime = time();

                    while (time() - $startTime < $maxDuration) {
                        try {
                            // Check for new notifications since last check
                            $newNotifications = $user->notifications()
                                ->where('created_at', '>', $lastCheck)
                                ->where('status', 'sent')
                                ->latest()
                                ->get();

                            foreach ($newNotifications as $notification) {
                                $eventData = [
                                    'id' => $notification->id,
                                    'title' => $notification->title,
                                    'message' => $notification->message,
                                    'type' => $notification->type,
                                    'priority' => $notification->priority,
                                    'created_at' => $notification->created_at->toISOString(),
                                    'action_url' => $notification->action_url,
                                    'data' => $notification->data
                                ];

                                echo "data: " . json_encode($eventData) . "\n\n";

                                if (ob_get_level()) {
                                    ob_end_flush();
                                }
                                flush();
                            }

                            $lastCheck = now();

                            // Send heartbeat to keep connection alive
                            if (count($newNotifications) === 0) {
                                echo "data: " . json_encode([
                                    'type' => 'heartbeat',
                                    'timestamp' => now()->toISOString()
                                ]) . "\n\n";

                                if (ob_get_level()) {
                                    ob_end_flush();
                                }
                                flush();
                            }

                            // Check if client disconnected
                            if (connection_aborted()) {
                                break;
                            }

                            sleep($checkInterval);
                        } catch (\Exception $e) {
                            Log::error('Stream inner loop error: ' . $e->getMessage());
                            break;
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Stream function error: ' . $e->getMessage());
                    echo "data: " . json_encode([
                        'type' => 'error',
                        'message' => 'Stream error occurred'
                    ]) . "\n\n";
                    flush();
                }
            }, 200, $headers);
        } catch (\Exception $e) {
            Log::error('Notification stream error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to start notification stream'
            ], 500);
        }
    }
}
