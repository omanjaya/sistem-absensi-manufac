<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            // Payment type (hourly vs fixed)
            $table->enum('payment_type', ['hourly', 'fixed', 'daily'])->default('fixed')->after('user_id');
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('payment_type');
            $table->decimal('daily_rate', 10, 2)->nullable()->after('hourly_rate');

            // Working hours tracking
            $table->decimal('regular_hours', 8, 2)->default(0)->after('basic_salary');
            $table->decimal('overtime_hours_calculated', 8, 2)->default(0)->after('overtime_hours');
            $table->decimal('total_working_hours', 8, 2)->default(0)->after('overtime_hours_calculated');

            // Hourly payment calculations
            $table->decimal('regular_hours_amount', 12, 2)->default(0)->after('total_working_hours');
            $table->decimal('daily_rate_amount', 12, 2)->default(0)->after('regular_hours_amount');

            // Attendance summary
            $table->integer('total_present_days')->default(0)->after('daily_rate_amount');
            $table->integer('total_absent_days')->default(0)->after('total_present_days');
            $table->integer('total_late_days')->default(0)->after('total_absent_days');
            $table->integer('total_early_departure_days')->default(0)->after('total_late_days');

            // Deductions detail
            $table->decimal('late_penalty', 12, 2)->default(0)->after('total_early_departure_days');
            $table->decimal('absent_penalty', 12, 2)->default(0)->after('late_penalty');

            // Additional allowances detail
            $table->decimal('attendance_bonus', 12, 2)->default(0)->after('other_allowances');
            $table->decimal('performance_bonus', 12, 2)->default(0)->after('attendance_bonus');

            // Calculation notes
            $table->text('calculation_notes')->nullable()->after('calculation_details');
        });

        // Add foreign key for work period
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('work_period_id')->nullable()->after('basic_salary')->constrained('work_periods')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn([
                'payment_type',
                'hourly_rate',
                'daily_rate',
                'regular_hours',
                'overtime_hours_calculated',
                'total_working_hours',
                'regular_hours_amount',
                'daily_rate_amount',
                'total_present_days',
                'total_absent_days',
                'total_late_days',
                'total_early_departure_days',
                'late_penalty',
                'absent_penalty',
                'attendance_bonus',
                'performance_bonus',
                'calculation_notes'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['work_period_id']);
            $table->dropColumn('work_period_id');
        });
    }
};
