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
        Schema::create('work_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama periode kerja
            $table->text('description')->nullable();

            // Jam kerja per hari
            $table->json('work_schedule'); // JSON: {'monday': {'start': '08:00', 'end': '17:00'}, ...}

            // Pengaturan jam istirahat
            $table->json('break_times')->nullable(); // JSON: [{'start': '12:00', 'end': '13:00', 'name': 'Lunch'}]

            // Pengaturan toleransi
            $table->integer('late_tolerance_minutes')->default(15); // Toleransi terlambat
            $table->integer('early_departure_tolerance_minutes')->default(15); // Toleransi pulang cepat
            $table->integer('minimum_work_hours')->default(8); // Minimum jam kerja per hari

            // Pengaturan overtime
            $table->boolean('overtime_enabled')->default(true);
            $table->decimal('overtime_rate_multiplier', 4, 2)->default(1.5);
            $table->integer('overtime_minimum_minutes')->default(30); // Minimum untuk dihitung overtime

            // Pengaturan absensi
            $table->boolean('require_checkin')->default(true);
            $table->boolean('require_checkout')->default(true);
            $table->boolean('allow_early_checkin')->default(true);
            $table->integer('early_checkin_limit_minutes')->default(60); // Max 1 jam sebelum jam kerja

            // Scope aplikasi
            $table->json('departments')->nullable(); // Departemen yang menggunakan periode ini
            $table->json('roles')->nullable(); // Role yang menggunakan periode ini
            $table->boolean('is_default')->default(false); // Periode default

            // Status dan periode aktif
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();

            // Pengaturan hari kerja
            $table->json('working_days')->default('["monday","tuesday","wednesday","thursday","friday"]');
            $table->boolean('include_saturdays')->default(false);
            $table->boolean('include_sundays')->default(false);

            // Metadata
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('status');
            $table->index('is_default');
            $table->index(['effective_from', 'effective_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_periods');
    }
};
