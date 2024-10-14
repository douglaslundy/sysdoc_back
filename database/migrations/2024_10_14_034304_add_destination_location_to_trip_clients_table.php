<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDestinationLocationToTripClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trip_clients', function (Blueprint $table) {
            $table->string('destination_location', 50)->nullable()->after('person_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trip_clients', function (Blueprint $table) {
            $table->dropColumn('destination_location');
        });
    }
}
