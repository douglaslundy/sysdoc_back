<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('access_profiles') || Schema::hasColumn('access_profiles', 'client_history_view_enabled')) {
            return;
        }

        Schema::table('access_profiles', function (Blueprint $table) {
            $table->boolean('client_history_view_enabled')->default(false)->after('client_report_view_enabled');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('access_profiles', 'client_history_view_enabled')) {
            Schema::table('access_profiles', function (Blueprint $table) {
                $table->dropColumn('client_history_view_enabled');
            });
        }
    }
};
