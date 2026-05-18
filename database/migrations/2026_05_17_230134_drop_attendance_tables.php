<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('ended_calls');
        Schema::dropIfExists('calls');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('call_services');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Irreversible migration: dropped legacy attendance tables.
    }
};
