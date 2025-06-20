<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Salary Calculation Configuration
    |--------------------------------------------------------------------------
    |
    | These values define the salary calculation rules and rates
    | for the payroll system.
    |
    */

    'overtime_rate_per_hour' => env('OVERTIME_RATE_PER_HOUR', 25000),
    'tax_rate' => env('TAX_RATE', 0.05), // 5% tax rate
    'default_basic_salary' => env('DEFAULT_BASIC_SALARY', 5000000),

    /*
    |--------------------------------------------------------------------------
    | Deduction Rules
    |--------------------------------------------------------------------------
    |
    | Configure automatic deduction rules for payroll.
    |
    */

    'absent_day_deduction_rate' => env('ABSENT_DAY_DEDUCTION_RATE', 1.0), // Full day deduction
    'late_penalty_amount' => env('LATE_PENALTY_AMOUNT', 50000),
    'max_late_penalty_days' => env('MAX_LATE_PENALTY_DAYS', 5),

    /*
    |--------------------------------------------------------------------------
    | Allowance Configuration
    |--------------------------------------------------------------------------
    |
    | Default allowance amounts that can be added to salary.
    |
    */

    'default_allowances' => [
        'transport' => env('TRANSPORT_ALLOWANCE', 500000),
        'meal' => env('MEAL_ALLOWANCE', 300000),
        'communication' => env('COMMUNICATION_ALLOWANCE', 200000),
    ],

];
