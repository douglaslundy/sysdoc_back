<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_treatment_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('treatment_plan_id')->constrained('queue_treatment_plans')->cascadeOnDelete();
            $table->date('scheduled_date');
            $table->date('original_scheduled_date')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('reschedule_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_treatment_sessions');
    }
};
