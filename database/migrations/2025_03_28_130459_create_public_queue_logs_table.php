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
        Schema::create('public_queue_logs', function (Blueprint $table) {
            $table->id();
            $table->ipAddress('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('host_name')->nullable();
            $table->string('referer')->nullable();
            $table->timestamp('accessed_at')->useCurrent();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_queue_logs');
    }
};
