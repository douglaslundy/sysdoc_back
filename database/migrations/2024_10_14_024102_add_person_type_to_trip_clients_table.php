<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPersonTypeToTripClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('trip_clients', function (Blueprint $table) {
            // Adiciona a coluna 'person_type' com valores possíveis 'passenger' e 'companion'
            $table->enum('person_type', ['passenger', 'companion'])->default('passenger')->after('client_id');
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
            // Remove a coluna 'person_type' se o rollback for executado
            $table->dropColumn('person_type');
        });
    }
}
