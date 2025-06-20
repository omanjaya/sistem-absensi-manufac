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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date');

            // Clock in/out times
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->decimal('work_hours', 4, 2)->default(0);
            $table->enum('status', ['present', 'late', 'absent', 'partial'])->default('present');

            // Clock in location data
            $table->decimal('clock_in_latitude', 10, 8)->nullable();
            $table->decimal('clock_in_longitude', 11, 8)->nullable();
            $table->string('clock_in_location')->nullable();

            // Clock out location data
            $table->decimal('clock_out_latitude', 10, 8)->nullable();
            $table->decimal('clock_out_longitude', 11, 8)->nullable();
            $table->string('clock_out_location')->nullable();

            // Face recognition data
            $table->text('clock_in_photo')->nullable(); // Base64 image data
            $table->text('clock_out_photo')->nullable(); // Base64 image data
            $table->decimal('face_confidence', 5, 2)->nullable();

            // Notes
            $table->text('clock_in_notes')->nullable();
            $table->text('clock_out_notes')->nullable();
            $table->text('notes')->nullable(); // General notes

            // Additional metadata
            $table->json('meta_data')->nullable(); // For storing additional info

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'date']);
            $table->index('date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
