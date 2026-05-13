<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_monthly_acquisitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medicine_item_id')->constrained('medicine_items');
            $table->char('reference_month', 7);
            $table->decimal('acquired_quantity', 12, 2);
            $table->string('unit_measure', 20);
            $table->string('source_document')->nullable();
            $table->string('note', 1000)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users');
            $table->timestamps();

            $table->unique(['medicine_item_id', 'reference_month'], 'uniq_medicine_monthly_acq');
            $table->index('reference_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_monthly_acquisitions');
    }
};

