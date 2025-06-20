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
        Schema::table('users', function (Blueprint $table) {
            // Add missing columns that are used in Excel import
            $table->string('nip')->nullable()->unique()->after('employee_id');
            $table->text('address')->nullable()->after('phone');
            $table->date('birth_date')->nullable()->after('address');
            $table->enum('gender', ['Laki-laki', 'Perempuan', 'male', 'female'])->nullable()->after('birth_date');
            $table->string('education')->nullable()->after('basic_salary');
            $table->string('university')->nullable()->after('education');
            $table->decimal('allowance', 12, 2)->default(0)->after('university');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nip',
                'address',
                'birth_date',
                'gender',
                'education',
                'university',
                'allowance'
            ]);
        });
    }
};
