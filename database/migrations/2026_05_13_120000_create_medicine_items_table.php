<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_items', function (Blueprint $table) {
            $table->id();
            $table->string('internal_code')->unique();
            $table->string('brand_name')->nullable();
            $table->string('active_ingredient');
            $table->string('concentration');
            $table->string('pharmaceutical_form');
            $table->string('presentation');
            $table->string('unit_measure', 20);
            $table->string('ean_code')->nullable();
            $table->boolean('is_free_distribution')->default(true);
            $table->boolean('is_controlled')->default(false);
            $table->boolean('active')->default(true);
            $table->text('technical_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['active', 'is_free_distribution']);
            $table->index('active_ingredient');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_items');
    }
};
