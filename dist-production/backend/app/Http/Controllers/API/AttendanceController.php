<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\User;
use App\Services\FaceRecognitionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AttendanceExport;

class AttendanceController extends Controller
{
    protected $faceService;

    public function __construct(FaceRecognitionService $faceService)
    {
        $this->faceService = $faceService;
    }

    /**
     * Display a listing of attendances
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Attendance::with('user:id,name,employee_id,department');

        // If employee, only show their own attendances
        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        }

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('department')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Sort by latest first
        $query->orderBy('date', 'desc')->orderBy('clock_in', 'desc');

        $attendances = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $attendances
        ]);
    }

    /**
     * Store a newly created attendance
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|string', // Base64 image
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'type' => 'required|in:clock_in,clock_out',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $today = Carbon::today();

        try {
            // Check existing attendance for today
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            $type = $request->type;

            // Validate attendance type
            if ($type === 'clock_in' && $existingAttendance && $existingAttendance->clock_in) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already clocked in today'
                ], 400);
            }

            if ($type === 'clock_out' && (!$existingAttendance || !$existingAttendance->clock_in)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You must clock in first before clocking out'
                ], 400);
            }

            if ($type === 'clock_out' && $existingAttendance && $existingAttendance->clock_out) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already clocked out today'
                ], 400);
            }

            // Face recognition
            $faceResult = $this->faceService->recognizeFace($request->photo);

            if (!$faceResult['success']) {
                Log::warning('Face recognition failed for attendance', [
                    'user_id' => $user->id,
                    'error' => $faceResult['message']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $faceResult['message']
                ], 400);
            }

            // Verify recognized user matches authenticated user
            if ($faceResult['user_id'] != $user->id) {
                Log::warning('Face mismatch in attendance', [
                    'authenticated_user' => $user->id,
                    'recognized_user' => $faceResult['user_id']
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Face recognition mismatch. Please try again.'
                ], 400);
            }

            // Validate location (office radius check)
            $officeLatitude = config('attendance.office_latitude', -6.2088);
            $officeLongitude = config('attendance.office_longitude', 106.8456);
            $officeRadius = config('attendance.office_radius', 100); // meters

            $distance = $this->calculateDistance(
                $request->latitude,
                $request->longitude,
                $officeLatitude,
                $officeLongitude
            );

            if ($distance > $officeRadius) {
                return response()->json([
                    'success' => false,
                    'message' => "You are {$distance}m away from office. Please be within {$officeRadius}m radius.",
                    'distance' => $distance,
                    'allowed_radius' => $officeRadius
                ], 400);
            }

            $currentTime = Carbon::now();

            if ($type === 'clock_in') {
                // Create new attendance record
                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $today,
                    'clock_in' => $currentTime,
                    'clock_in_latitude' => $request->latitude,
                    'clock_in_longitude' => $request->longitude,
                    'clock_in_photo' => $request->photo,
                    'clock_in_notes' => $request->notes,
                    'face_confidence' => $faceResult['confidence'] ?? 0,
                    'status' => $this->determineStatus($currentTime, 'clock_in')
                ]);

                $message = 'Clock in successful';
            } else {
                // Update existing attendance record
                $existingAttendance->update([
                    'clock_out' => $currentTime,
                    'clock_out_latitude' => $request->latitude,
                    'clock_out_longitude' => $request->longitude,
                    'clock_out_photo' => $request->photo,
                    'clock_out_notes' => $request->notes,
                ]);

                // Calculate work hours
                $existingAttendance->calculateWorkHours();
                $attendance = $existingAttendance->fresh();

                $message = 'Clock out successful';
            }

            Log::info('Attendance recorded successfully', [
                'user_id' => $user->id,
                'type' => $type,
                'attendance_id' => $attendance->id,
                'distance' => $distance
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $attendance->load('user:id,name,employee_id')
            ]);
        } catch (\Exception $e) {
            Log::error('Attendance creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Attendance recording failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Get today's attendance
     */
    public function today(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->with('user:id,name,employee_id')
            ->first();

        return response()->json([
            'success' => true,
            'data' => $attendance,
            'message' => $attendance ? 'Today\'s attendance found' : 'No attendance record for today yet'
        ]);
    }

    /**
     * Display the specified attendance
     */
    public function show(Request $request, Attendance $attendance)
    {
        $user = $request->user();

        // Employees can only view their own attendance
        if ($user->role === 'employee' && $attendance->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $attendance->load('user:id,name,employee_id,department,position')
        ]);
    }

    /**
     * Update the specified attendance (admin only)
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|in:present,late,early_departure,absent',
            'clock_in' => 'sometimes|date_format:H:i',
            'clock_out' => 'sometimes|date_format:H:i',
            'notes' => 'nullable|string|max:500',
            'admin_notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only(['status', 'notes', 'admin_notes']);

            // Update clock times if provided
            if ($request->filled('clock_in')) {
                $clockIn = Carbon::createFromFormat('H:i', $request->clock_in);
                $updateData['clock_in'] = $attendance->date->copy()->setTime($clockIn->hour, $clockIn->minute);
            }

            if ($request->filled('clock_out')) {
                $clockOut = Carbon::createFromFormat('H:i', $request->clock_out);
                $updateData['clock_out'] = $attendance->date->copy()->setTime($clockOut->hour, $clockOut->minute);
            }

            $attendance->update($updateData);

            // Recalculate work hours if clock times were updated
            if ($request->filled('clock_in') || $request->filled('clock_out')) {
                $attendance->calculateWorkHours();
            }

            Log::info('Attendance updated by admin', [
                'attendance_id' => $attendance->id,
                'updated_by' => $request->user()->id,
                'changes' => $updateData
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Attendance updated successfully',
                'data' => $attendance->fresh()->load('user:id,name,employee_id')
            ]);
        } catch (\Exception $e) {
            Log::error('Attendance update failed', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Attendance update failed'
            ], 500);
        }
    }

    /**
     * Remove the specified attendance (admin only)
     */
    public function destroy(Request $request, Attendance $attendance)
    {
        try {
            Log::info('Attendance deleted by admin', [
                'attendance_id' => $attendance->id,
                'user_id' => $attendance->user_id,
                'deleted_by' => $request->user()->id
            ]);

            $attendance->delete();

            return response()->json([
                'success' => true,
                'message' => 'Attendance deleted successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Attendance deletion failed', [
                'attendance_id' => $attendance->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Attendance deletion failed'
            ], 500);
        }
    }

    /**
     * Export attendances to Excel
     */
    public function export(Request $request)
    {
        $user = $request->user();

        // Build query
        $query = Attendance::with('user:id,name,employee_id,department,position');

        // If employee, only export their own attendances
        if ($user->role === 'employee') {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        if ($request->filled('department')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        try {
            $filename = 'attendance_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new AttendanceExport($query),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Attendance export failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Earth radius in meters

        $deltaLat = deg2rad($lat2 - $lat1);
        $deltaLon = deg2rad($lon2 - $lon1);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($deltaLon / 2) * sin($deltaLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c); // Distance in meters
    }

    /**
     * Determine attendance status based on time
     */
    private function determineStatus(Carbon $time, string $type): string
    {
        $workStartTime = config('attendance.work_start_time', '08:00');
        $workEndTime = config('attendance.work_end_time', '17:00');
        $lateThreshold = config('attendance.late_threshold_minutes', 15);

        if ($type === 'clock_in') {
            $startTime = Carbon::createFromFormat('H:i', $workStartTime);
            $lateTime = $startTime->copy()->addMinutes($lateThreshold);

            if ($time->format('H:i') <= $lateTime->format('H:i')) {
                return 'present';
            } else {
                return 'late';
            }
        }

        return 'present';
    }
}
