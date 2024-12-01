<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trip_clients', function (Blueprint $table) {
            $table->string('departure_location', 50)->nullable()->after('person_type');
            $table->string('phone', 20)->nullable()->after('person_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_clients', function (Blueprint $table) {
            $table->dropColumn('departure_location');
            $table->dropColumn('phone');
        });
    }
};
