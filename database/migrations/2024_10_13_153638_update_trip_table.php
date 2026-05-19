<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UpdateTripTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->string('obs', 300)->nullable()->after('departure_time');
            $table->date('departure_date')->nullable()->after('departure_time');
        });

        DB::statement('ALTER TABLE `trips` MODIFY `departure_time` TIME NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn('obs');
            $table->dropColumn('departure_date');
        });

        DB::statement('ALTER TABLE `trips` MODIFY `departure_time` TIMESTAMP NOT NULL');
    }
}
