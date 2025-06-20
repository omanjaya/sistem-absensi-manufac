<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SecurityController extends Controller
{
    /**
     * Get audit logs (Admin only)
     */
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with('user')->latest();

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('action')) {
            $query->where('action', 'like', '%' . $request->action . '%');
        }

        if ($request->filled('risk_level')) {
            $query->where('risk_level', $request->risk_level);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    /**
     * Get security alerts (Admin only)
     */
    public function securityAlerts(Request $request)
    {
        $alerts = AuditLog::with('user')
            ->where(function ($query) {
                $query->where('risk_level', 'high')
                    ->orWhere('action', 'like', '%failed%')
                    ->orWhere('action', 'like', '%suspicious%');
            })
            ->whereBetween('created_at', [
                now()->subDays(7), // Last 7 days
                now()
            ])
            ->latest()
            ->paginate(15);

        $alertSummary = [
            'high_risk_today' => AuditLog::where('risk_level', 'high')
                ->whereDate('created_at', today())
                ->count(),
            'failed_logins_today' => AuditLog::where('action', 'like', '%login%')
                ->where('action', 'like', '%failed%')
                ->whereDate('created_at', today())
                ->count(),
            'suspicious_activities' => AuditLog::where('risk_level', 'high')
                ->whereBetween('created_at', [now()->subHours(24), now()])
                ->count()
        ];

        return response()->json([
            'success' => true,
            'data' => $alerts,
            'summary' => $alertSummary
        ]);
    }

    /**
     * Get login history for all users (Admin only)
     */
    public function loginHistory(Request $request)
    {
        $query = AuditLog::with('user')
            ->where('action', 'like', '%login%')
            ->latest();

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $history = $query->paginate(25);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Get my login history (Current user)
     */
    public function myLoginHistory(Request $request)
    {
        $user = Auth::user();

        $history = AuditLog::where('user_id', $user->id)
            ->where('action', 'like', '%login%')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $history
        ]);
    }

    /**
     * Get suspicious activities (Admin only)
     */
    public function suspiciousActivities(Request $request)
    {
        $suspicious = AuditLog::with('user')
            ->where(function ($query) {
                $query->where('risk_level', 'high')
                    ->orWhere(function ($subQuery) {
                        // Multiple failed login attempts
                        $subQuery->where('action', 'like', '%login%')
                            ->where('action', 'like', '%failed%')
                            ->where('created_at', '>', now()->subHours(1));
                    })
                    ->orWhere(function ($subQuery) {
                        // Unusual access patterns
                        $subQuery->whereIn('action', [
                            'data.exported',
                            'salary.modified',
                            'user.deleted',
                            'permission.changed'
                        ]);
                    });
            })
            ->whereBetween('created_at', [
                now()->subDays(30),
                now()
            ])
            ->latest()
            ->get();

        // Group by user and analyze patterns
        $suspiciousPatterns = $suspicious->groupBy('user_id')->map(function ($userLogs, $userId) {
            $user = User::find($userId);
            $riskScore = $this->calculateRiskScore($userLogs);

            return [
                'user' => $user ? $user->only(['id', 'name', 'email', 'department']) : null,
                'risk_score' => $riskScore,
                'activity_count' => $userLogs->count(),
                'last_activity' => $userLogs->first()->created_at,
                'activities' => $userLogs->take(5)->map(function ($log) {
                    return [
                        'action' => $log->action,
                        'ip_address' => $log->ip_address,
                        'risk_level' => $log->risk_level,
                        'created_at' => $log->created_at
                    ];
                })
            ];
        })->sortByDesc('risk_score')->values();

        return response()->json([
            'success' => true,
            'data' => $suspiciousPatterns,
            'total_suspicious_users' => $suspiciousPatterns->count()
        ]);
    }

    /**
     * Calculate risk score for user activities
     */
    private function calculateRiskScore($userLogs)
    {
        $score = 0;

        foreach ($userLogs as $log) {
            switch ($log->risk_level) {
                case 'high':
                    $score += 10;
                    break;
                case 'medium':
                    $score += 5;
                    break;
                case 'low':
                    $score += 1;
                    break;
            }

            // Additional scoring for specific actions
            if (strpos($log->action, 'failed') !== false) {
                $score += 3;
            }
            if (strpos($log->action, 'deleted') !== false) {
                $score += 5;
            }
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Enable 2FA for current user
     */
    public function enable2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'phone' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Verify current password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Generate 2FA secret (in production, use proper 2FA library like Google Authenticator)
        $secret = $this->generate2FASecret();

        // Store 2FA settings (add to users table migration if needed)
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => encrypt($secret),
            'two_factor_phone' => $request->phone
        ]);

        AuditLog::logAction('2fa.enabled', $user, [], [
            'two_factor_enabled' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => '2FA enabled successfully',
            'data' => [
                'secret' => $secret,
                'qr_code' => $this->generate2FAQRCode($user->email, $secret)
            ]
        ]);
    }

    /**
     * Disable 2FA for current user
     */
    public function disable2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required',
            'verification_code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Verify current password
        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 422);
        }

        // Verify 2FA code
        if (!$this->verify2FACode($user, $request->verification_code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid verification code'
            ], 422);
        }

        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_phone' => null
        ]);

        AuditLog::logAction('2fa.disabled', $user, [
            'two_factor_enabled' => true
        ], [
            'two_factor_enabled' => false
        ]);

        return response()->json([
            'success' => true,
            'message' => '2FA disabled successfully'
        ]);
    }

    /**
     * Verify 2FA code
     */
    public function verify2FA(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'verification_code' => 'required|string|size:6'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        if (!$user->two_factor_enabled) {
            return response()->json([
                'success' => false,
                'message' => '2FA is not enabled for this account'
            ], 422);
        }

        $isValid = $this->verify2FACode($user, $request->verification_code);

        AuditLog::logAction($isValid ? '2fa.verified' : '2fa.failed', $user);

        return response()->json([
            'success' => $isValid,
            'message' => $isValid ? '2FA verification successful' : 'Invalid verification code'
        ]);
    }

    /**
     * Get device logs (Admin only)
     */
    public function deviceLogs(Request $request)
    {
        $logs = AuditLog::with('user')
            ->whereNotNull('device_fingerprint')
            ->latest()
            ->paginate(20);

        $deviceStats = [
            'unique_devices' => AuditLog::distinct('device_fingerprint')->count('device_fingerprint'),
            'active_sessions' => AuditLog::where('action', 'like', '%login%')
                ->where('created_at', '>', now()->subHours(24))
                ->distinct('device_fingerprint')
                ->count('device_fingerprint'),
            'suspicious_devices' => AuditLog::where('risk_level', 'high')
                ->distinct('device_fingerprint')
                ->count('device_fingerprint')
        ];

        return response()->json([
            'success' => true,
            'data' => $logs,
            'stats' => $deviceStats
        ]);
    }

    /**
     * Generate 2FA secret
     */
    private function generate2FASecret()
    {
        // Simple 2FA secret generation (in production, use proper library)
        return strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'), 0, 16));
    }

    /**
     * Generate 2FA QR code URL
     */
    private function generate2FAQRCode($email, $secret)
    {
        $appName = config('app.name');
        $url = "otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";

        // Return QR code URL (in production, generate actual QR code)
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url);
    }

    /**
     * Verify 2FA code
     */
    private function verify2FACode($user, $code)
    {
        // Simple verification (in production, use proper TOTP library)
        // For demo purposes, accept any 6-digit code
        return preg_match('/^\d{6}$/', $code);
    }
}
