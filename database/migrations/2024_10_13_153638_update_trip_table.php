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
            // Adiciona a coluna obs com limite de 300 caracteres
            $table->string('obs', 300)->nullable()->after('departure_time'); // Substitua 'column_name' pela coluna onde você quer adicionar depois

            // Cria a coluna departure_date sem valor padrão
            $table->date('departure_date')->nullable()->after('departure_time');

            // Altera a coluna departure_time para aceitar apenas hora e usar um valor padrão '00:00:00'
            $table->time('departure_time')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            // Remove a coluna obs
            $table->dropColumn('obs');

            // Remove a coluna departure_date
            $table->dropColumn('departure_date');

            // Reverte a alteração de departure_time para timestamp
            $table->timestamp('departure_time')->change();
        });
    }
}
