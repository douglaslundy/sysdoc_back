<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_medicine_panel_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('filter_is_free_distribution')->default(false);
            $table->boolean('filter_is_controlled')->default(false);
            $table->boolean('filter_is_judicial_order')->default(false);
            $table->boolean('filter_is_high_cost')->default(false);
            $table->boolean('filter_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_medicine_panel_settings');
    }
};
