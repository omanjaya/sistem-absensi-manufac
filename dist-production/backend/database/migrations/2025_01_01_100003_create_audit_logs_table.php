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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('action'); // e.g., 'user.created', 'attendance.modified'
            $table->string('model_type')->nullable(); // Polymorphic model type
            $table->unsignedBigInteger('model_id')->nullable(); // Polymorphic model id
            $table->json('old_values')->nullable(); // Previous values
            $table->json('new_values')->nullable(); // New values
            $table->text('url')->nullable(); // Request URL
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('session_id')->nullable();
            $table->enum('risk_level', ['low', 'medium', 'high'])->default('low');
            $table->json('context')->nullable(); // Additional context
            $table->json('location_data')->nullable(); // Geographic data
            $table->string('device_fingerprint')->nullable(); // Device identification
            $table->timestamps();

            // Indexes for performance and security monitoring
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['risk_level', 'created_at']);
            $table->index(['model_type', 'model_id']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['device_fingerprint']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
