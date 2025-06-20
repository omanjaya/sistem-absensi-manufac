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
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama hari libur
            $table->text('description')->nullable();

            // Tanggal libur
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_days'); // Jumlah hari libur

            // Tipe libur
            $table->enum('type', [
                'national', // Hari libur nasional
                'religious', // Hari libur keagamaan
                'school', // Libur sekolah
                'semester', // Libur semester
                'custom' // Libur khusus
            ])->default('school');

            // Status
            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');

            // Scope aplikasi
            $table->boolean('applies_to_all')->default(true); // Apply ke semua pegawai
            $table->json('departments')->nullable(); // Departemen spesifik jika tidak semua
            $table->json('roles')->nullable(); // Role spesifik jika tidak semua

            // Pengaturan absensi
            $table->boolean('allow_attendance')->default(false); // Boleh absen di hari libur
            $table->boolean('overtime_eligible')->default(false); // Bisa dapat overtime
            $table->decimal('overtime_multiplier', 4, 2)->default(1.5); // Multiplier overtime

            // Recurring holiday
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['yearly', 'monthly', 'custom'])->nullable();
            $table->json('recurrence_data')->nullable(); // Data recurring pattern

            // Metadata
            $table->string('color')->default('#dc3545'); // Warna untuk kalender
            $table->json('metadata')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['start_date', 'end_date']);
            $table->index('type');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('holidays');
    }
};
