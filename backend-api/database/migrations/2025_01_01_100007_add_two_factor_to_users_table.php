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
            $table->boolean('two_factor_enabled')->default(false)->after('remember_token');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');
            $table->string('two_factor_phone')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_verified_at')->nullable()->after('two_factor_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'two_factor_enabled',
                'two_factor_secret',
                'two_factor_phone',
                'two_factor_verified_at'
            ]);
        });
    }
};
