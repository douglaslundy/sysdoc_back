<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_channel_configs', function (Blueprint $table) {
            $table->id();
            $table->string('canal', 40)->unique();
            $table->boolean('ativo')->default(false);
            $table->json('configuracao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channel_configs');
    }
};
