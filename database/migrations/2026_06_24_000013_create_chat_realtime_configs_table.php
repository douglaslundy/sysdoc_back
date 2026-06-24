<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_realtime_configs', function (Blueprint $table) {
            $table->id();
            $table->string('engine', 20)->default('pusher');
            $table->boolean('active')->default(false);
            $table->text('app_id')->nullable();
            $table->text('app_key')->nullable();
            $table->text('app_secret')->nullable();
            $table->string('cluster', 40)->nullable();
            $table->string('host', 255)->nullable();
            $table->unsignedSmallInteger('port')->nullable();
            $table->string('scheme', 10)->default('https');
            $table->boolean('use_tls')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_realtime_configs');
    }
};
