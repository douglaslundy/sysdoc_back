<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_profiles', function (Blueprint $table) {
            $table->boolean('client_trips_view_enabled')->default(false)->after('almoxarifado_deliver_enabled');
            $table->boolean('client_report_view_enabled')->default(false)->after('client_trips_view_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('access_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'client_trips_view_enabled',
                'client_report_view_enabled',
            ]);
        });
    }
};
