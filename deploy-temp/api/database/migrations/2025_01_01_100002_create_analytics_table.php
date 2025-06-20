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
        Schema::create('analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('department')->nullable();
            $table->enum('metric_type', [
                'attendance',
                'productivity',
                'performance',
                'overtime',
                'leave_patterns',
                'cost_analysis',
                'efficiency',
                'trends',
                'predictions',
                'compliance'
            ]);
            $table->string('metric_name');
            $table->decimal('metric_value', 10, 2);
            $table->json('metric_data')->nullable(); // Detailed breakdown
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'quarterly', 'yearly', 'custom']);
            $table->date('period_start');
            $table->date('period_end');
            $table->timestamp('calculated_at');
            $table->json('context')->nullable(); // Additional context data
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'metric_type', 'period_start']);
            $table->index(['department', 'metric_type']);
            $table->index(['metric_type', 'period_type']);
            $table->index(['calculated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics');
    }
};
