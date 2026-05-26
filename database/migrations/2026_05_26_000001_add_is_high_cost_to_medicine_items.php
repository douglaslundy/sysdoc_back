<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_items', function (Blueprint $table) {
            $table->boolean('is_high_cost')->default(false)->after('is_judicial_order');
        });
    }

    public function down(): void
    {
        Schema::table('medicine_items', function (Blueprint $table) {
            $table->dropColumn('is_high_cost');
        });
    }
};
