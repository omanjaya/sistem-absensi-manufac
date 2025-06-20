<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalaryExport;

class SalaryController extends Controller
{
    /**
     * Display a listing of salaries
     */
    public function index(Request $request)
    {
        $query = Salary::with('user:id,name,employee_id,department,position', 'generatedBy:id,name');

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('department')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('department', $request->department);
            });
        }

        // Sort by latest first
        $query->orderBy('year', 'desc')->orderBy('month', 'desc');

        $salaries = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $salaries
        ]);
    }

    /**
     * Generate salaries for all employees for a specific month
     */
    public function generate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|between:1,12',
            'year' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $month = $request->month;
        $year = $request->year;
        $generatedBy = $request->user()->id;

        try {
            // Check if salaries already generated for this period
            $existingSalaries = Salary::where('month', $month)
                ->where('year', $year);

            if ($request->filled('user_ids')) {
                $existingSalaries->whereIn('user_id', $request->user_ids);
            }

            if ($existingSalaries->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Salaries already generated for this period. Delete existing records first.'
                ], 400);
            }

            // Get users to generate salaries for
            $usersQuery = User::where('status', 'active');

            if ($request->filled('user_ids')) {
                $usersQuery->whereIn('id', $request->user_ids);
            }

            $users = $usersQuery->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active users found to generate salaries'
                ], 400);
            }

            $generatedSalaries = [];

            foreach ($users as $user) {
                $salaryData = $this->calculateSalary($user, $month, $year);

                $salary = Salary::create([
                    'user_id' => $user->id,
                    'month' => $month,
                    'year' => $year,
                    'period' => sprintf('%04d-%02d', $year, $month),
                    'basic_salary' => $salaryData['basic_salary'],
                    'overtime_hours' => $salaryData['overtime_hours'],
                    'overtime_rate' => config('salary.overtime_rate_per_hour', 25000),
                    'overtime_amount' => $salaryData['overtime_pay'],
                    'transport_allowance' => 0,
                    'meal_allowance' => 0,
                    'other_allowances' => $salaryData['allowances'],
                    'tax_deduction' => $salaryData['tax'],
                    'insurance_deduction' => 0,
                    'other_deductions' => $salaryData['deductions'],
                    'gross_salary' => $salaryData['gross_salary'],
                    'net_salary' => $salaryData['net_salary'],
                    'generated_by' => $generatedBy,
                    'status' => 'draft',
                    'calculation_details' => json_encode([
                        'work_days' => $salaryData['work_days'],
                        'present_days' => $salaryData['present_days'],
                        'absent_days' => $salaryData['absent_days'],
                        'late_days' => $salaryData['late_days']
                    ])
                ]);

                $generatedSalaries[] = $salary->load('user:id,name,employee_id,department');
            }

            Log::info('Salaries generated', [
                'month' => $month,
                'year' => $year,
                'count' => count($generatedSalaries),
                'generated_by' => $generatedBy
            ]);

            return response()->json([
                'success' => true,
                'message' => count($generatedSalaries) . ' salaries generated successfully',
                'data' => $generatedSalaries
            ]);
        } catch (\Exception $e) {
            Log::error('Salary generation failed', [
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Salary generation failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Display the specified salary
     */
    public function show(Salary $salary)
    {
        return response()->json([
            'success' => true,
            'data' => $salary->load([
                'user:id,name,employee_id,department,position',
                'generatedBy:id,name'
            ])
        ]);
    }

    /**
     * Update the specified salary
     */
    public function update(Request $request, Salary $salary)
    {
        if ($salary->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot update paid salaries'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'allowances' => 'sometimes|numeric|min:0',
            'deductions' => 'sometimes|numeric|min:0',
            'overtime_hours' => 'sometimes|numeric|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $updateData = $request->only(['allowances', 'deductions', 'overtime_hours', 'notes']);

            // Recalculate salary if financial data changed
            if ($request->filled(['allowances', 'deductions', 'overtime_hours'])) {
                $overtimeRate = config('salary.overtime_rate_per_hour', 25000);
                $taxRate = config('salary.tax_rate', 0.05);

                $overtimeHours = $request->overtime_hours ?? $salary->overtime_hours;
                $allowances = $request->allowances ?? $salary->other_allowances;
                $deductions = $request->deductions ?? $salary->other_deductions;

                $overtimePay = $overtimeHours * $overtimeRate;
                $grossSalary = $salary->basic_salary + $overtimePay + $allowances;
                $tax = $grossSalary * $taxRate;
                $netSalary = $grossSalary - $tax - $deductions;

                $updateData['overtime_amount'] = $overtimePay;
                $updateData['gross_salary'] = $grossSalary;
                $updateData['tax'] = $tax;
                $updateData['net_salary'] = $netSalary;
            }

            $salary->update($updateData);

            Log::info('Salary updated', [
                'salary_id' => $salary->id,
                'updated_by' => $request->user()->id,
                'changes' => array_keys($updateData)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Salary updated successfully',
                'data' => $salary->fresh()->load('user:id,name,employee_id')
            ]);
        } catch (\Exception $e) {
            Log::error('Salary update failed', [
                'salary_id' => $salary->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Salary update failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Finalize salary (change status to finalized)
     */
    public function finalize(Request $request, Salary $salary)
    {
        if ($salary->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'Only draft salaries can be finalized'
            ], 400);
        }

        try {
            $salary->update([
                'status' => 'finalized',
                'finalized_at' => now()
            ]);

            Log::info('Salary finalized', [
                'salary_id' => $salary->id,
                'finalized_by' => $request->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Salary finalized successfully',
                'data' => $salary->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Salary finalization failed', [
                'salary_id' => $salary->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Salary finalization failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Mark salary as paid
     */
    public function markPaid(Request $request, Salary $salary)
    {
        if ($salary->status !== 'finalized') {
            return response()->json([
                'success' => false,
                'message' => 'Only finalized salaries can be marked as paid'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:100',
            'payment_reference' => 'nullable|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $salary->update([
                'status' => 'paid',
                'paid_at' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference
            ]);

            Log::info('Salary marked as paid', [
                'salary_id' => $salary->id,
                'marked_by' => $request->user()->id,
                'payment_method' => $request->payment_method
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Salary marked as paid successfully',
                'data' => $salary->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Mark salary as paid failed', [
                'salary_id' => $salary->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Operation failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Export salaries to Excel
     */
    public function export(Request $request)
    {
        // Build query
        $query = Salary::with('user:id,name,employee_id,department,position');

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('month')) {
            $query->where('month', $request->month);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        try {
            $filename = 'salary_' . now()->format('Y-m-d_H-i-s') . '.xlsx';

            return Excel::download(
                new SalaryExport($query),
                $filename
            );
        } catch (\Exception $e) {
            Log::error('Salary export failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Calculate salary for a user for a specific month
     */
    private function calculateSalary(User $user, int $month, int $year): array
    {
        $startDate = Carbon::createFromDate($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // Get work days (excluding weekends)
        $workDays = $startDate->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, $endDate);

        // Get attendances for the month
        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $presentDays = $attendances->where('status', 'present')->count() + $attendances->where('status', 'late')->count();
        $lateDays = $attendances->where('status', 'late')->count();

        // Only count actual absent records, not missing data
        $actualAbsentDays = $attendances->where('status', 'absent')->count();

        // Calculate overtime hours
        $overtimeHours = $attendances->sum('overtime_hours');

        // Calculate salary components
        $basicSalary = $user->basic_salary;
        $overtimeRate = config('salary.overtime_rate_per_hour', 25000);
        $overtimePay = $overtimeHours * $overtimeRate;
        $allowances = 0; // Can be customized based on user/company policies

        // Calculate deductions only for actual absent days
        $absentPenalty = 0;
        if ($workDays > 0 && $actualAbsentDays > 0) {
            $absentPenalty = ($basicSalary / $workDays) * $actualAbsentDays;
        }

        $grossSalary = $basicSalary + $overtimePay + $allowances;
        $taxRate = config('salary.tax_rate', 0.05);
        $tax = $grossSalary * $taxRate;
        $netSalary = $grossSalary - $tax - $absentPenalty;

        return [
            'basic_salary' => $basicSalary,
            'work_days' => $workDays,
            'present_days' => $presentDays,
            'absent_days' => $actualAbsentDays,
            'late_days' => $lateDays,
            'overtime_hours' => $overtimeHours,
            'overtime_pay' => $overtimePay,
            'allowances' => $allowances,
            'deductions' => $absentPenalty,
            'gross_salary' => $grossSalary,
            'tax' => $tax,
            'net_salary' => $netSalary
        ];
    }
}
