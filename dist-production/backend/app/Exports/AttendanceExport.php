<?php

namespace App\Exports;

use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Illuminate\Database\Eloquent\Builder;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
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
            'Date',
            'Employee ID',
            'Employee Name',
            'Department',
            'Position',
            'Clock In',
            'Clock Out',
            'Work Hours',
            'Overtime Hours',
            'Status',
            'Late Duration (mins)',
            'Clock In Location',
            'Clock Out Location',
            'Face Confidence',
            'Notes'
        ];
    }

    public function map($attendance): array
    {
        return [
            $attendance->date->format('Y-m-d'),
            $attendance->user->employee_id ?? '-',
            $attendance->user->name ?? '-',
            $attendance->user->department ?? '-',
            $attendance->user->position ?? '-',
            $attendance->clock_in ? $attendance->clock_in->format('H:i:s') : '-',
            $attendance->clock_out ? $attendance->clock_out->format('H:i:s') : '-',
            $attendance->work_hours ? number_format($attendance->work_hours, 2) . ' hours' : '-',
            $attendance->overtime_hours ? number_format($attendance->overtime_hours, 2) . ' hours' : '0 hours',
            ucfirst($attendance->status),
            $attendance->late_duration ?? '0',
            $this->formatLocation($attendance->clock_in_latitude, $attendance->clock_in_longitude),
            $this->formatLocation($attendance->clock_out_latitude, $attendance->clock_out_longitude),
            $attendance->face_confidence ? number_format($attendance->face_confidence * 100, 1) . '%' : '-',
            $this->formatNotes($attendance)
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
                    'color' => ['rgb' => '4F81BD']
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],

            // All cells alignment
            'A:O' => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER
                ]
            ],

            // Status column styling
            'J:J' => [
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER
                ]
            ]
        ];
    }

    private function formatLocation($latitude, $longitude): string
    {
        if ($latitude && $longitude) {
            return number_format($latitude, 6) . ', ' . number_format($longitude, 6);
        }
        return '-';
    }

    private function formatNotes($attendance): string
    {
        $notes = [];

        if ($attendance->clock_in_notes) {
            $notes[] = 'In: ' . $attendance->clock_in_notes;
        }

        if ($attendance->clock_out_notes) {
            $notes[] = 'Out: ' . $attendance->clock_out_notes;
        }

        if ($attendance->admin_notes) {
            $notes[] = 'Admin: ' . $attendance->admin_notes;
        }

        return empty($notes) ? '-' : implode(' | ', $notes);
    }
}
