<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddYearToVehiclesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->integer('year')->unsigned()->nullable()->comment('Year of the vehicle (4 digits)')->check('year <= 9999')->after('color'); // Adiciona a coluna 'year' com limite de 4 dígitos
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropColumn('year'); // Remove a coluna 'year' se a migration for revertida
        });
    }
}
