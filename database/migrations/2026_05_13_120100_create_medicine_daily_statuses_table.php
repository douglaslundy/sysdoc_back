<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_daily_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_item_id')->constrained('medicine_items');
            $table->date('reference_date');
            $table->enum('availability_status', ['available', 'unavailable']);
            $table->decimal('available_quantity', 12, 2)->nullable();
            $table->date('restock_forecast_date')->nullable();
            $table->string('public_note', 1000)->nullable();
            $table->timestamp('published_site_at')->nullable();
            $table->timestamp('published_panel_at')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['medicine_item_id', 'reference_date'], 'uniq_medicine_daily_status');
            $table->index(['reference_date', 'availability_status'], 'idx_medicine_daily_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_daily_statuses');
    }
};

