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
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_type'); // ios, android, web, desktop
            $table->string('device_name')->nullable();
            $table->text('push_token')->nullable(); // FCM/APNS token
            $table->string('device_id')->unique(); // Unique device identifier
            $table->string('app_version')->nullable();
            $table->string('os_version')->nullable();
            $table->json('capabilities')->nullable(); // Device capabilities
            $table->json('preferences')->nullable(); // Notification preferences
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['device_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
