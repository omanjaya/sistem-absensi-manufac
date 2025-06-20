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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('period'); // Format: YYYY-MM
            $table->year('year');
            $table->tinyInteger('month');

            // Salary components
            $table->decimal('basic_salary', 12, 2);
            $table->decimal('overtime_hours', 8, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);

            // Allowances
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('meal_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);

            // Deductions
            $table->decimal('tax_deduction', 12, 2)->default(0);
            $table->decimal('insurance_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);

            // Totals
            $table->decimal('gross_salary', 12, 2);
            $table->decimal('net_salary', 12, 2);

            // Status and meta
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('generated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->json('calculation_details')->nullable();

            $table->timestamps();

            // Indexes and constraints
            $table->unique(['user_id', 'period']);
            $table->index(['year', 'month']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};
