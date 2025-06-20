<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Holiday;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class HolidayController extends Controller
{
    /**
     * Display holidays with DataTables server-side processing
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Holiday::select(['holidays.*']);

            return DataTables::of($query)
                ->addColumn('duration', function ($holiday) {
                    if ($holiday->duration_days == 1) {
                        return '1 hari';
                    }
                    return $holiday->duration_days . ' hari';
                })
                ->addColumn('date_range', function ($holiday) {
                    if ($holiday->start_date->eq($holiday->end_date)) {
                        return $holiday->start_date->format('d M Y');
                    }
                    return $holiday->start_date->format('d M Y') . ' - ' . $holiday->end_date->format('d M Y');
                })
                ->addColumn('type_badge', function ($holiday) {
                    $badges = [
                        'national' => '<span class="badge bg-danger">Nasional</span>',
                        'religious' => '<span class="badge bg-info">Keagamaan</span>',
                        'school' => '<span class="badge bg-warning">Sekolah</span>',
                        'semester' => '<span class="badge bg-primary">Semester</span>',
                        'custom' => '<span class="badge bg-secondary">Custom</span>'
                    ];
                    return $badges[$holiday->type] ?? $holiday->type;
                })
                ->addColumn('status_badge', function ($holiday) {
                    $badges = [
                        'active' => '<span class="badge bg-success">Aktif</span>',
                        'inactive' => '<span class="badge bg-secondary">Tidak Aktif</span>',
                        'cancelled' => '<span class="badge bg-danger">Dibatalkan</span>'
                    ];
                    return $badges[$holiday->status] ?? $holiday->status;
                })
                ->addColumn('scope', function ($holiday) {
                    if ($holiday->applies_to_all) {
                        return '<span class="text-success">Semua Pegawai</span>';
                    }

                    $scope = [];
                    if ($holiday->departments) {
                        $scope[] = 'Dept: ' . implode(', ', $holiday->departments);
                    }
                    if ($holiday->roles) {
                        $scope[] = 'Role: ' . implode(', ', $holiday->roles);
                    }

                    return implode('<br>', $scope);
                })
                ->addColumn('action', function ($holiday) {
                    $canEdit = auth()->user()->can('holiday.edit');
                    $canDelete = auth()->user()->can('holiday.delete');

                    $actions = '<div class="btn-group" role="group">';
                    $actions .= '<button type="button" class="btn btn-sm btn-info" onclick="viewHoliday(' . $holiday->id . ')">
                                    <i class="fas fa-eye"></i>
                                 </button>';

                    if ($canEdit) {
                        $actions .= '<button type="button" class="btn btn-sm btn-warning" onclick="editHoliday(' . $holiday->id . ')">
                                        <i class="fas fa-edit"></i>
                                     </button>';
                    }

                    if ($canDelete) {
                        $actions .= '<button type="button" class="btn btn-sm btn-danger" onclick="deleteHoliday(' . $holiday->id . ')">
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
                    if ($request->filled('type')) {
                        $query->where('type', $request->type);
                    }

                    if ($request->filled('status')) {
                        $query->where('status', $request->status);
                    }

                    if ($request->filled('year')) {
                        $query->whereYear('start_date', $request->year);
                    }
                })
                ->rawColumns(['type_badge', 'status_badge', 'scope', 'action'])
                ->make(true);
        }

        // Return metadata for non-AJAX requests
        return response()->json([
            'success' => true,
            'data' => [
                'types' => $this->getHolidayTypes(),
                'years' => $this->getAvailableYears()
            ]
        ]);
    }

    /**
     * Store a new holiday
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'type' => 'required|in:national,religious,school,semester,custom',
            'status' => 'sometimes|in:active,inactive,cancelled',
            'applies_to_all' => 'boolean',
            'departments' => 'nullable|array',
            'roles' => 'nullable|array',
            'allow_attendance' => 'boolean',
            'overtime_eligible' => 'boolean',
            'overtime_multiplier' => 'nullable|numeric|min:1|max:5',
            'is_recurring' => 'boolean',
            'recurrence_type' => 'nullable|in:yearly,monthly,custom',
            'color' => 'nullable|string|max:7',
            'notes' => 'nullable|string'
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

            // Calculate duration
            $startDate = Carbon::parse($request->start_date);
            $endDate = Carbon::parse($request->end_date);
            $data['duration_days'] = $startDate->diffInDays($endDate) + 1;

            // Set defaults
            $data['status'] = $data['status'] ?? 'active';
            $data['applies_to_all'] = $data['applies_to_all'] ?? true;
            $data['allow_attendance'] = $data['allow_attendance'] ?? false;
            $data['overtime_eligible'] = $data['overtime_eligible'] ?? false;
            $data['overtime_multiplier'] = $data['overtime_multiplier'] ?? 1.5;
            $data['color'] = $data['color'] ?? '#dc3545';

            $holiday = Holiday::create($data);

            Log::info('Holiday created', [
                'holiday_id' => $holiday->id,
                'name' => $holiday->name,
                'created_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hari libur berhasil dibuat',
                'data' => $holiday
            ], 201);
        } catch (\Exception $e) {
            Log::error('Holiday creation failed', [
                'error' => $e->getMessage(),
                'input' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat hari libur. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Display specific holiday
     */
    public function show(Holiday $holiday)
    {
        return response()->json([
            'success' => true,
            'data' => $holiday
        ]);
    }

    /**
     * Update holiday
     */
    public function update(Request $request, Holiday $holiday)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
            'type' => 'sometimes|in:national,religious,school,semester,custom',
            'status' => 'sometimes|in:active,inactive,cancelled',
            'applies_to_all' => 'boolean',
            'departments' => 'nullable|array',
            'roles' => 'nullable|array',
            'allow_attendance' => 'boolean',
            'overtime_eligible' => 'boolean',
            'overtime_multiplier' => 'nullable|numeric|min:1|max:5',
            'is_recurring' => 'boolean',
            'recurrence_type' => 'nullable|in:yearly,monthly,custom',
            'color' => 'nullable|string|max:7',
            'notes' => 'nullable|string'
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

            // Recalculate duration if dates changed
            if ($request->has(['start_date', 'end_date'])) {
                $startDate = Carbon::parse($request->start_date ?? $holiday->start_date);
                $endDate = Carbon::parse($request->end_date ?? $holiday->end_date);
                $data['duration_days'] = $startDate->diffInDays($endDate) + 1;
            }

            $holiday->update($data);

            Log::info('Holiday updated', [
                'holiday_id' => $holiday->id,
                'updated_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hari libur berhasil diperbarui',
                'data' => $holiday->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Holiday update failed', [
                'holiday_id' => $holiday->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui hari libur. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Delete holiday
     */
    public function destroy(Holiday $holiday)
    {
        try {
            $holidayId = $holiday->id;
            $holiday->delete();

            Log::info('Holiday deleted', [
                'holiday_id' => $holidayId,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Hari libur berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            Log::error('Holiday deletion failed', [
                'holiday_id' => $holiday->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus hari libur. Silakan coba lagi.'
            ], 500);
        }
    }

    /**
     * Get holidays for calendar view
     */
    public function calendar(Request $request)
    {
        $year = $request->get('year', date('Y'));
        $month = $request->get('month');

        $query = Holiday::where('status', 'active')
            ->whereYear('start_date', $year);

        if ($month) {
            $query->whereMonth('start_date', $month);
        }

        $holidays = $query->get()->map(function ($holiday) {
            return [
                'id' => $holiday->id,
                'title' => $holiday->name,
                'start' => $holiday->start_date->format('Y-m-d'),
                'end' => $holiday->end_date->addDay()->format('Y-m-d'), // FullCalendar exclusive end
                'color' => $holiday->color,
                'description' => $holiday->description,
                'type' => $holiday->type,
                'allDay' => true
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $holidays
        ]);
    }

    /**
     * Check if date is holiday
     */
    public function isHoliday(Request $request)
    {
        $date = Carbon::parse($request->get('date', today()));

        $holiday = Holiday::where('status', 'active')
            ->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'is_holiday' => !!$holiday,
                'holiday' => $holiday,
                'allow_attendance' => $holiday?->allow_attendance ?? true,
                'overtime_eligible' => $holiday?->overtime_eligible ?? false,
                'overtime_multiplier' => $holiday?->overtime_multiplier ?? 1.0
            ]
        ]);
    }

    /**
     * Get holiday types
     */
    private function getHolidayTypes()
    {
        return [
            'national' => 'Hari Libur Nasional',
            'religious' => 'Hari Libur Keagamaan',
            'school' => 'Libur Sekolah',
            'semester' => 'Libur Semester',
            'custom' => 'Libur Khusus'
        ];
    }

    /**
     * Get available years
     */
    private function getAvailableYears()
    {
        $currentYear = date('Y');
        $years = range($currentYear - 2, $currentYear + 2);

        return array_combine($years, $years);
    }
}
