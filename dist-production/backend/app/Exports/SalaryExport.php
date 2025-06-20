<?php

namespace App\Exports;

use App\Models\Salary;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Illuminate\Database\Eloquent\Builder;

class SalaryExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $query;

    public function __construct(Builder $query)
    {
        $this->query = $query;
    }

    public function query()
    {
        return $this->query->with('user:id,name,employee_id,department,position');
    }

    public function headings(): array
    {
        return [
            'Period',
            'Employee ID',
            'Employee Name',
            'Department',
            'Position',
            'Work Days',
            'Present Days',
            'Absent Days',
            'Late Days',
            'Attendance Rate',
            'Basic Salary',
            'Overtime Hours',
            'Overtime Pay',
            'Allowances',
            'Deductions',
            'Gross Salary',
            'Tax',
            'Net Salary',
            'Status',
            'Payment Method',
            'Payment Date',
            'Notes'
        ];
    }

    public function map($salary): array
    {
        return [
            $salary->formatted_period ?? $this->formatPeriod($salary->month, $salary->year),
            $salary->user->employee_id ?? '-',
            $salary->user->name ?? '-',
            $salary->user->department ?? '-',
            $salary->user->position ?? '-',
            $salary->work_days,
            $salary->present_days,
            $salary->absent_days,
            $salary->late_days,
            $salary->attendance_rate . '%',
            $this->formatCurrency($salary->basic_salary),
            number_format($salary->overtime_hours, 2) . ' hours',
            $this->formatCurrency($salary->overtime_pay),
            $this->formatCurrency($salary->allowances),
            $this->formatCurrency($salary->deductions),
            $this->formatCurrency($salary->gross_salary),
            $this->formatCurrency($salary->tax),
            $this->formatCurrency($salary->net_salary),
            ucfirst($salary->status),
            $salary->payment_method ?? '-',
            $salary->paid_at ? $salary->paid_at->format('Y-m-d') : '-',
            $salary->notes ?? '-'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Header row styling
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF']
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'color' => ['rgb' => '2E7D32']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],

            // All cells alignment
            'A:V' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],

            // Salary columns formatting (currency)
            'K:R' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_RIGHT
                ],
                'numberFormat' => [
                    'formatCode' => NumberFormat::FORMAT_CURRENCY_USD
                ]
            ],

            // Percentage column
            'J:J' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ],

            // Status column styling
            'S:S' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ],

            // Work days columns
            'F:I' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ]
        ];
    }

    private function formatCurrency($amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function formatPeriod($month, $year): string
    {
        $monthNames = [
            1 => 'January',
            2 => 'February',
            3 => 'March',
            4 => 'April',
            5 => 'May',
            6 => 'June',
            7 => 'July',
            8 => 'August',
            9 => 'September',
            10 => 'October',
            11 => 'November',
            12 => 'December'
        ];

        return $monthNames[$month] . ' ' . $year;
    }
}
