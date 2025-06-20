<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WorkPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class WorkPeriodController extends Controller
{
    /**
     * Display work periods with DataTables server-side processing
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = WorkPeriod::select(['work_periods.*']);

            return DataTables::of($query)
                ->addColumn('schedule_info', function ($period) {
                    $schedule = $period->schedule;
                    if (empty($schedule)) {
                        return '<span class="text-muted">Tidak ada jadwal</span>';
                    }

                    $info = [];
                    foreach ($schedule as $day => $times) {
                        if ($times && isset($times['start']) && isset($times['end'])) {
                            $dayName = $this->getDayName($day);
                            $info[] = $dayName . ': ' . $times['start'] . ' - ' . $times['end'];
                        }
                    }

                    return implode('<br>', array_slice($info, 0, 3)) .
                        (count($info) > 3 ? '<br><small class="text-muted">+' . (count($info) - 3) . ' hari lainnya</small>' : '');
                })
                ->addColumn('break_info', function ($period) {
                    $breaks = $period->break_times;
                    if (empty($breaks)) {
                        return '<span class="text-muted">Tidak ada istirahat</span>';
                    }

                    $info = [];
                    foreach ($breaks as $break) {
                        if (isset($break['start']) && isset($break['end'])) {
                            $info[] = $break['start'] . ' - ' . $break['end'];
                        }
                    }

                    return implode('<br>', $info);
                })
                ->addColumn('overtime_info', function ($period) {
                    $overtime = $period->overtime_settings;
                    if (!$overtime || !isset($overtime['enabled']) || !$overtime['enabled']) {
                        return '<span class="text-muted">Tidak diizinkan</span>';
                    }

                    $multiplier = $overtime['multiplier'] ?? 1.5;
                    $maxHours = $overtime['max_hours_per_day'] ?? '-';

                    return "Multiplier: {$multiplier}x<br>Max: {$maxHours} jam/hari";
                })
                ->addColumn('status_badge', function ($period) {
                    $badges = [
                        'active' => '<span class="badge bg-success">Aktif</span>',
                        'inactive' => '<span class="badge bg-secondary">Tidak Aktif</span>',
                        'archived' => '<span class="badge bg-dark">Arsip</span>'
                    ];
                    return $badges[$period->status] ?? $period->status;
                })
                ->addColumn('action', function ($period) {
                    $canEdit = auth()->user()->can('work_period.edit');
                    $canDelete = auth()->user()->can('work_period.delete');

                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<button type="button" class="btn btn-sm btn-info" onclick="viewWorkPeriod(' . $period->id . ')">
                                    <i class="fas fa-eye"></i>
                                 </button>';

                    if ($canEdit) {
                        $actions .= '<button type="button" class="btn btn-sm btn-warning" onclick="editWorkPeriod(' . $period->id . ')">
                                        <i class="fas fa-edit"></i>
                                     </button>';
                    }

                    if ($canDelete && $period->status !== 'active') {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteWorkPeriod(' . $period->id . ')">
                                        <i class="fas fa-trash"></i>
                                     </button>';
                    }

                    $actions .= '</div>';
                    return $actions;
                })
                ->filter(function ($query) use ($request) {
                    // Global search
                    if ($request->has('search') && $request->search['value']) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%")
                                ->orWhere('description', 'like', "%{$search}%");
                        });
                    }

                    // Filters
                    if ($request->filled('status')) {
                        $query->where('status', $request->status);
                    }

                    if ($request->filled('department')) {
                        $query->whereJsonContains('applicable_departments', $request->department);
                    }
                })
                ->rawColumns(['schedule_info', 'break_info', 'overtime_info', 'status_badge', 'action'])
                ->make(true);
        }

        // Return metadata for non-AJAX requests
        return response()->json([
            'success' => true,
            'data' => [
                'departments' => $this->getAvailableDepartments()
            ]
        ]);
    }

    /**
     * Store a new work period
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'required|array',
            'schedule.*.start' => 'required|string',
            'schedule.*.end' => 'required|string',
            'break_times' => 'nullable|array',
            'break_times.*.start' => 'required|string',
            'break_times.*.end' => 'required|string',
            'break_times.*.name' => 'nullable|string',
            'tolerance_settings' => 'nullable|array',
            'overtime_settings' => 'nullable|array',
            'applicable_departments' => 'nullable|array',
            'status' => 'sometimes|in:active,inactive,archived'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();

            // Set defaults
            $data['status'] = $data['status'] ?? 'active';
            $data['tolerance_settings'] = $data['tolerance_settings'] ?? [
                'late_tolerance_minutes' => 15,
                'early_departure_tolerance_minutes' => 15
            ];
            $data['overtime_settings'] = $data['overtime_settings'] ?? [
                'enabled' => false,
                'multiplier' => 1.5,
                'max_hours_per_day' => 4,
                'requires_approval' => true
            ];

            // Calculate total work hours per week
            $totalHours = $this->calculateWeeklyHours($data['schedule'], $data['break_times'] ?? []);
            $data['total_hours_per_week'] = $totalHours;

            $workPeriod = WorkPeriod::create($data);

            Log::info('Work period created', [
                'work_period_id' => $workPeriod->id,
                'name' => $workPeriod->name,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Periode kerja berhasil dibuat',
                'data' => $workPeriod
            ], 201);
        } catch (\Exception $e) {
            Log::error('Work period creation failed', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat periode kerja. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Display specific work period
     */
    public function show(WorkPeriod $workPeriod)
    {
        return response()->json([
            'success' => true,
            'data' => $workPeriod
        ]);
    }

    /**
     * Update work period
     */
    public function update(Request $request, WorkPeriod $workPeriod)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'schedule' => 'sometimes|array',
            'schedule.*.start' => 'required|string',
            'schedule.*.end' => 'required|string',
            'break_times' => 'nullable|array',
            'break_times.*.start' => 'required|string',
            'break_times.*.end' => 'required|string',
            'break_times.*.name' => 'nullable|string',
            'tolerance_settings' => 'nullable|array',
            'overtime_settings' => 'nullable|array',
            'applicable_departments' => 'nullable|array',
            'status' => 'sometimes|in:active,inactive,archived'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();

            // Recalculate total hours if schedule or breaks changed
            if ($request->has(['schedule']) || $request->has(['break_times'])) {
                $schedule = $request->get('schedule', $workPeriod->schedule);
                $breaks = $request->get('break_times', $workPeriod->break_times);
                $data['total_hours_per_week'] = $this->calculateWeeklyHours($schedule, $breaks);
            }

            $workPeriod->update($data);

            Log::info('Work period updated', [
                'work_period_id' => $workPeriod->id,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Periode kerja berhasil diperbarui',
                'data' => $workPeriod->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Work period update failed', [
                'work_period_id' => $workPeriod->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui periode kerja. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Delete work period
     */
    public function destroy(WorkPeriod $workPeriod)
    {
        if ($workPeriod->status === 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Tidak dapat menghapus periode kerja yang masih aktif'
            ], 422);
        }

        try {
            $workPeriodId = $workPeriod->id;
            $workPeriod->delete();

            Log::info('Work period deleted', [
                'work_period_id' => $workPeriodId,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Periode kerja berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Work period deletion failed', [
                'work_period_id' => $workPeriod->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus periode kerja. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Get work schedule for specific date
     */
    public function getScheduleForDate(Request $request)
    {
        $date = Carbon::parse($request->get('date', today()));
        $dayOfWeek = strtolower($date->format('l'));

        $workPeriod = WorkPeriod::where('status', 'active')->first();

        if (!$workPeriod || !isset($workPeriod->schedule[$dayOfWeek])) {
            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Tidak ada jadwal kerja untuk hari ini'
            ]);
        }

        $schedule = $workPeriod->schedule[$dayOfWeek];

        return response()->json([
            'success' => true,
            'data' => [
                'work_period' => $workPeriod,
                'schedule' => $schedule,
                'break_times' => $workPeriod->break_times,
                'tolerance_settings' => $workPeriod->tolerance_settings,
                'overtime_settings' => $workPeriod->overtime_settings
            ]
        ]);
    }

    /**
     * Calculate weekly working hours
     */
    private function calculateWeeklyHours($schedule, $breakTimes = [])
    {
        $totalMinutes = 0;
        $breakMinutes = 0;

        // Calculate break duration in minutes
        foreach ($breakTimes as $break) {
            if (isset($break['start']) && isset($break['end'])) {
                $start = Carbon::createFromFormat('H:i', $break['start']);
                $end = Carbon::createFromFormat('H:i', $break['end']);
                $breakMinutes += $start->diffInMinutes($end);
            }
        }

        // Calculate work hours for each day
        foreach ($schedule as $daySchedule) {
            if (isset($daySchedule['start']) && isset($daySchedule['end'])) {
                $start = Carbon::createFromFormat('H:i', $daySchedule['start']);
                $end = Carbon::createFromFormat('H:i', $daySchedule['end']);
                $dayMinutes = $start->diffInMinutes($end) - $breakMinutes;
                $totalMinutes += max(0, $dayMinutes);
            }
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Get day name in Indonesian
     */
    private function getDayName($day)
    {
        $days = [
            'monday' => 'Senin',
            'tuesday' => 'Selasa',
            'wednesday' => 'Rabu',
            'thursday' => 'Kamis',
            'friday' => 'Jumat',
            'saturday' => 'Sabtu',
            'sunday' => 'Minggu'
        ];

        return $days[strtolower($day)] ?? ucfirst($day);
    }

    /**
     * Get available departments
     */
    private function getAvailableDepartments()
    {
        return [
            'administration' => 'Administrasi',
            'teaching' => 'Pengajar',
            'support' => 'Pendukung',
            'management' => 'Manajemen'
        ];
    }
}
