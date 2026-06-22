<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('painel_esus_presences', function (Blueprint $table) {
            $table->id();
            $table->string('cnes', 20)->unique();
            $table->string('panel_name')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('painel_esus_presences');
    }
};
