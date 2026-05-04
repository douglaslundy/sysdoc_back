<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_page_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_profile_id')->constrained('access_profiles')->cascadeOnDelete();
            $table->foreignId('system_page_id')->constrained('system_pages')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['access_profile_id', 'system_page_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_page_permissions');
    }
};
