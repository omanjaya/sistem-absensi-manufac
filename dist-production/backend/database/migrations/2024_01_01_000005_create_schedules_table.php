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
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title'); // Nama mata pelajaran atau kegiatan
            $table->text('description')->nullable();

            // Jadwal harian
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
            $table->time('start_time');
            $table->time('end_time');

            // Periode jadwal
            $table->date('start_date');
            $table->date('end_date')->nullable(); // Null = recurring schedule

            // Lokasi dan kelas
            $table->string('location')->nullable(); // Ruang kelas
            $table->string('class_name')->nullable(); // Nama kelas
            $table->integer('student_count')->default(0);

            // Status dan tipe
            $table->enum('status', ['active', 'inactive', 'cancelled'])->default('active');
            $table->enum('schedule_type', ['teaching', 'meeting', 'training', 'other'])->default('teaching');

            // Pengaturan recurring
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_type', ['weekly', 'monthly', 'custom'])->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // Additional data
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'day_of_week']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
