<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Work Schedule Configuration
    |--------------------------------------------------------------------------
    |
    | These values define the standard work hours and attendance rules
    | for the attendance system.
    |
    */

    'work_start_time' => env('WORK_START_TIME', '08:00'),
    'work_end_time' => env('WORK_END_TIME', '17:00'),
    'late_threshold_minutes' => env('LATE_THRESHOLD_MINUTES', 15),
    'early_checkout_threshold_minutes' => env('EARLY_CHECKOUT_THRESHOLD_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Office Location Configuration
    |--------------------------------------------------------------------------
    |
    | These coordinates define the office location for GPS validation
    | during attendance check-in/check-out.
    |
    */

    'office_latitude' => env('OFFICE_LATITUDE', -6.2088),
    'office_longitude' => env('OFFICE_LONGITUDE', 106.8456),
    'office_radius' => env('OFFICE_RADIUS', 100), // meters

    /*
    |--------------------------------------------------------------------------
    | Overtime Configuration
    |--------------------------------------------------------------------------
    |
    | Configure overtime calculation rules and rates.
    |
    */

    'overtime_threshold_hours' => env('OVERTIME_THRESHOLD_HOURS', 8),
    'overtime_rate_multiplier' => env('OVERTIME_RATE_MULTIPLIER', 1.5),

];
