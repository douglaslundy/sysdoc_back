<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_speciality_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('speciality_id')->constrained('specialities')->cascadeOnDelete();
            $table->boolean('can_view')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_insert')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'speciality_id']);
        });

        (new \App\Services\Authorization\UserSpecialityPermissionBackfiller())->run();
    }

    public function down(): void
    {
        Schema::dropIfExists('user_speciality_permissions');
    }
};
