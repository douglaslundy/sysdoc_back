<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_items', function (Blueprint $table) {
            $table->boolean('is_judicial_order')->default(false)->after('is_controlled');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_items', function (Blueprint $table) {
            $table->dropColumn('is_judicial_order');
        });
    }
};
