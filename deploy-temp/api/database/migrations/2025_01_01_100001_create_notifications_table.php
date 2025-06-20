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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->enum('type', [
                'attendance_reminder',
                'leave_approved',
                'leave_rejected',
                'overtime_alert',
                'salary_generated',
                'schedule_changed',
                'holiday_announced',
                'system_maintenance',
                'security_alert',
                'birthday_reminder',
                'performance_review',
                'training_reminder'
            ]);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('channel', ['app', 'email', 'sms', 'push', 'whatsapp'])->default('app');
            $table->json('data')->nullable(); // Additional notification data
            $table->timestamp('read_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
            $table->string('reference_type')->nullable(); // Polymorphic reference
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('action_url')->nullable(); // URL for notification action
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'read_at']);
            $table->index(['type', 'priority']);
            $table->index(['scheduled_at', 'status']);
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
