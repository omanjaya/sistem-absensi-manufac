<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period',
        'year',
        'month',
        'basic_salary',
        'overtime_hours',
        'overtime_rate',
        'overtime_amount',
        'transport_allowance',
        'meal_allowance',
        'other_allowances',
        'tax_deduction',
        'insurance_deduction',
        'other_deductions',
        'gross_salary',
        'net_salary',
        'status',
        'finalized_at',
        'paid_at',
        'generated_by',
        'calculation_details'
    ];

    protected function casts(): array
    {
        return [
            'basic_salary' => 'decimal:2',
            'overtime_hours' => 'decimal:2',
            'overtime_rate' => 'decimal:2',
            'overtime_amount' => 'decimal:2',
            'transport_allowance' => 'decimal:2',
            'meal_allowance' => 'decimal:2',
            'other_allowances' => 'decimal:2',
            'tax_deduction' => 'decimal:2',
            'insurance_deduction' => 'decimal:2',
            'other_deductions' => 'decimal:2',
            'gross_salary' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'finalized_at' => 'datetime',
            'paid_at' => 'datetime',
            'calculation_details' => 'array',
        ];
    }

    /**
     * Get the user that owns the salary
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who generated the salary
     */
    public function generatedBy()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    /**
     * Check if salary is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Check if salary is finalized
     */
    public function isFinalized(): bool
    {
        return $this->status === 'finalized';
    }

    /**
     * Check if salary is paid
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Get formatted period (e.g., "January 2024")
     */
    public function getFormattedPeriodAttribute(): string
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

        return $monthNames[$this->month] . ' ' . $this->year;
    }

    /**
     * Get attendance rate percentage
     */
    public function getAttendanceRateAttribute(): float
    {
        if ($this->work_days == 0) {
            return 0;
        }

        return round(($this->present_days / $this->work_days) * 100, 2);
    }

    /**
     * Scope for specific month and year
     */
    public function scopeForPeriod($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }

    /**
     * Scope for draft salaries
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for finalized salaries
     */
    public function scopeFinalized($query)
    {
        return $query->where('status', 'finalized');
    }

    /**
     * Scope for paid salaries
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope for current year
     */
    public function scopeCurrentYear($query)
    {
        return $query->where('year', now()->year);
    }

    /**
     * Scope for specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
