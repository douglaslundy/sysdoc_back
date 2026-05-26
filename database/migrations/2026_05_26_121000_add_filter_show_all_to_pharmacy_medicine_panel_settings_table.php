<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacy_medicine_panel_settings', function (Blueprint $table) {
            $table->boolean('filter_show_all')->default(false)->after('filter_active');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacy_medicine_panel_settings', function (Blueprint $table) {
            $table->dropColumn('filter_show_all');
        });
    }
};
