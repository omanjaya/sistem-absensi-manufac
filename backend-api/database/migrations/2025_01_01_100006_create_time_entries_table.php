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
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('attendance_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('task_name');
            $table->text('description')->nullable();
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->integer('duration_minutes')->nullable(); // Calculated duration
            $table->enum('entry_type', ['regular', 'overtime', 'break', 'meeting', 'training'])->default('regular');
            $table->enum('status', ['active', 'paused', 'completed', 'cancelled'])->default('active');
            $table->decimal('hourly_rate', 8, 2)->nullable(); // Override project rate
            $table->boolean('billable')->default(true);
            $table->json('tags')->nullable(); // Task tags/categories
            $table->json('metadata')->nullable(); // Additional tracking data
            $table->timestamps();

            $table->index(['user_id', 'start_time']);
            $table->index(['project_id', 'start_time']);
            $table->index(['status', 'start_time']);
            $table->index(['billable', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};
