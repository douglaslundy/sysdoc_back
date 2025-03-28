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
        Schema::create('qrcode_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('queue_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->text('location')->nullable();
            $table->string('referer')->nullable();
            $table->timestamp('accessed_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qrcode_logs');
    }
};
