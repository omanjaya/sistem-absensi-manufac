<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    /**
     * Display schedules with DataTables server-side processing
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Schedule::with('user')->select(['schedules.*']);

            return DataTables::of($query)
                ->addColumn('teacher_name', function ($schedule) {
                    return $schedule->user ? $schedule->user->name : '-';
                })
                ->addColumn('employee_id', function ($schedule) {
                    return $schedule->user ? $schedule->user->employee_id : '-';
                })
                ->addColumn('day_name', function ($schedule) {
                    return $this->getDayName($schedule->day_of_week);
                })
                ->addColumn('time_range', function ($schedule) {
                    return $schedule->start_time . ' - ' . $schedule->end_time;
                })
                ->addColumn('duration', function ($schedule) {
                    $start = Carbon::createFromFormat('H:i', $schedule->start_time);
                    $end = Carbon::createFromFormat('H:i', $schedule->end_time);
                    return $start->diffInMinutes($end) . ' menit';
                })
                ->addColumn('status_badge', function ($schedule) {
                    $badges = [
                        'active' => '<span class="badge bg-success">Aktif</span>',
                        'inactive' => '<span class="badge bg-secondary">Tidak Aktif</span>',
                        'cancelled' => '<span class="badge bg-danger">Dibatalkan</span>'
                    ];
                    return $badges[$schedule->status] ?? $schedule->status;
                })
                ->addColumn('action', function ($schedule) {
                    $canEdit = auth()->user()->can('schedule.edit');
                    $canDelete = auth()->user()->can('schedule.delete');

                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<button type="button" class="btn btn-sm btn-info" onclick="viewSchedule(' . $schedule->id . ')">
                                    <i class="fas fa-eye"></i>
                                 </button>';

                    if ($canEdit) {
                        $actions .= '<button type="button" class="btn btn-sm btn-warning" onclick="editSchedule(' . $schedule->id . ')">
                                        <i class="fas fa-edit"></i>
                                     </button>';
                    }

                    if ($canDelete) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteSchedule(' . $schedule->id . ')">
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
                            $q->where('subject', 'like', "%{$search}%")
                                ->orWhere('class_name', 'like', "%{$search}%")
                                ->orWhere('room_number', 'like', "%{$search}%")
                                ->orWhereHas('user', function ($userQuery) use ($search) {
                                    $userQuery->where('name', 'like', "%{$search}%");
                                });
                        });
                    }

                    // Filters
                    if ($request->filled('user_id')) {
                        $query->where('user_id', $request->user_id);
                    }

                    if ($request->filled('day_of_week')) {
                        $query->where('day_of_week', $request->day_of_week);
                    }

                    if ($request->filled('status')) {
                        $query->where('status', $request->status);
                    }

                    if ($request->filled('subject')) {
                        $query->where('subject', 'like', "%{$request->subject}%");
                    }
                })
                ->rawColumns(['status_badge', 'action'])
                ->make(true);
        }

        // Return metadata for non-AJAX requests
        return response()->json([
            'success' => true,
            'data' => [
                'teachers' => $this->getTeachers(),
                'subjects' => $this->getSubjects()
            ]
        ]);
    }

    /**
     * Store a new schedule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'subject' => 'required|string|max:255',
            'class_name' => 'required|string|max:255',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
            'room_number' => 'nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive,cancelled',
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check for schedule conflicts
            $conflict = $this->checkScheduleConflict($request->all());
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal bentrok dengan jadwal yang sudah ada',
                    'conflict' => $conflict
                ], 409);
            }

            $data = $request->all();
            $data['status'] = $data['status'] ?? 'active';
            $data['is_recurring'] = $data['is_recurring'] ?? true;
            $data['effective_date'] = $data['effective_date'] ?? now()->format('Y-m-d');

            $schedule = Schedule::create($data);

            Log::info('Schedule created', [
                'schedule_id' => $schedule->id,
                'user_id' => $schedule->user_id,
                'subject' => $schedule->subject,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dibuat',
                'data' => $schedule->load('user')
            ], 201);
        } catch (\Exception $e) {
            Log::error('Schedule creation failed', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat jadwal. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Display specific schedule
     */
    public function show(Schedule $schedule)
    {
        return response()->json([
            'success' => true,
            'data' => $schedule->load('user')
        ]);
    }

    /**
     * Update schedule
     */
    public function update(Request $request, Schedule $schedule)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'sometimes|exists:users,id',
            'subject' => 'sometimes|string|max:255',
            'class_name' => 'sometimes|string|max:255',
            'day_of_week' => 'sometimes|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'start_time' => 'sometimes|string',
            'end_time' => 'sometimes|string',
            'room_number' => 'nullable|string|max:50',
            'status' => 'sometimes|in:active,inactive,cancelled',
            'notes' => 'nullable|string',
            'is_recurring' => 'boolean',
            'effective_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:effective_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check for schedule conflicts (exclude current schedule)
            $data = $request->all();
            $conflict = $this->checkScheduleConflict($data, $schedule->id);
            if ($conflict) {
                return response()->json([
                    'success' => false,
                    'message' => 'Jadwal bentrok dengan jadwal yang sudah ada',
                    'conflict' => $conflict
                ], 409);
            }

            $schedule->update($data);

            Log::info('Schedule updated', [
                'schedule_id' => $schedule->id,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil diperbarui',
                'data' => $schedule->fresh()->load('user')
            ]);
        } catch (\Exception $e) {
            Log::error('Schedule update failed', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui jadwal. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Delete schedule
     */
    public function destroy(Schedule $schedule)
    {
        try {
            $scheduleId = $schedule->id;
            $schedule->delete();

            Log::info('Schedule deleted', [
                'schedule_id' => $scheduleId,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Jadwal berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Schedule deletion failed', [
                'schedule_id' => $schedule->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus jadwal. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Get weekly schedule
     */
    public function weekly(Request $request, $userId = null)
    {
        $query = Schedule::with('user')->where('status', 'active');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $schedules = $query->get()->groupBy('day_of_week');

        $weekData = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

        foreach ($days as $day) {
            $weekData[$day] = $schedules->get($day, collect())->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'subject' => $schedule->subject,
                    'class_name' => $schedule->class_name,
                    'teacher' => $schedule->user->name ?? '',
                    'time' => $schedule->start_time . ' - ' . $schedule->end_time,
                    'room' => $schedule->room_number,
                    'status' => $schedule->status
                ];
            });
        }

        return response()->json([
            'success' => true,
            'data' => $weekData
        ]);
    }

    /**
     * Get daily schedule
     */
    public function daily(Request $request, $date = null)
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        $dayOfWeek = strtolower($targetDate->format('l'));

        $schedules = Schedule::with('user')
            ->where('status', 'active')
            ->where('day_of_week', $dayOfWeek)
            ->orderBy('start_time')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'date' => $targetDate->format('Y-m-d'),
                'day_of_week' => $dayOfWeek,
                'day_name' => $this->getDayName($dayOfWeek),
                'schedules' => $schedules->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'subject' => $schedule->subject,
                        'class_name' => $schedule->class_name,
                        'teacher' => $schedule->user->name ?? '',
                        'employee_id' => $schedule->user->employee_id ?? '',
                        'time' => $schedule->start_time . ' - ' . $schedule->end_time,
                        'room' => $schedule->room_number,
                        'status' => $schedule->status,
                        'notes' => $schedule->notes
                    ];
                })
            ]
        ]);
    }

    /**
     * Get schedule conflicts
     */
    public function conflicts(Request $request)
    {
        $conflicts = Schedule::select('schedules.*')
            ->join('schedules as s2', function ($join) {
                $join->on('schedules.day_of_week', '=', 's2.day_of_week')
                    ->where('schedules.id', '!=', 's2.id')
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->where('schedules.start_time', '>=', 's2.start_time')
                                ->where('schedules.start_time', '<', 's2.end_time');
                        })->orWhere(function ($q) {
                            $q->where('schedules.end_time', '>', 's2.start_time')
                                ->where('schedules.end_time', '<=', 's2.end_time');
                        })->orWhere(function ($q) {
                            $q->where('schedules.start_time', '<=', 's2.start_time')
                                ->where('schedules.end_time', '>=', 's2.end_time');
                        });
                    });
            })
            ->where(function ($query) {
                $query->where('schedules.user_id', '=', 's2.user_id')
                    ->orWhere('schedules.room_number', '=', 's2.room_number');
            })
            ->where('schedules.status', 'active')
            ->with('user')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $conflicts
        ]);
    }

    /**
     * Check schedule conflict
     */
    private function checkScheduleConflict($data, $excludeId = null)
    {
        $query = Schedule::where('day_of_week', $data['day_of_week'])
            ->where('status', 'active')
            ->where(function ($q) use ($data) {
                $q->where(function ($timeQuery) use ($data) {
                    $timeQuery->where('start_time', '<=', $data['start_time'])
                        ->where('end_time', '>', $data['start_time']);
                })->orWhere(function ($timeQuery) use ($data) {
                    $timeQuery->where('start_time', '<', $data['end_time'])
                        ->where('end_time', '>=', $data['end_time']);
                })->orWhere(function ($timeQuery) use ($data) {
                    $timeQuery->where('start_time', '>=', $data['start_time'])
                        ->where('end_time', '<=', $data['end_time']);
                });
            })
            ->where(function ($q) use ($data) {
                // Check teacher conflict or room conflict
                $q->where('user_id', $data['user_id']);
                if (isset($data['room_number']) && $data['room_number']) {
                    $q->orWhere('room_number', $data['room_number']);
                }
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->with('user')->first();
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
     * Get available teachers
     */
    private function getTeachers()
    {
        return \App\Models\User::where('role', 'teacher')
            ->orWhere('role', 'admin')
            ->select('id', 'name', 'employee_id')
            ->get();
    }

    /**
     * Get available subjects
     */
    private function getSubjects()
    {
        return [
            'Matematika',
            'Bahasa Indonesia',
            'Bahasa Inggris',
            'IPA',
            'IPS',
            'Fisika',
            'Kimia',
            'Biologi',
            'Sejarah',
            'Geografi',
            'Ekonomi',
            'Sosiologi',
            'Seni Budaya',
            'Pendidikan Jasmani',
            'Agama',
            'PPKn'
        ];
    }
}
