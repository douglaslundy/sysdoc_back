<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Liga o acompanhante ao passageiro (id da própria trip_clients). Sem FK: se o passageiro sai,
    // o código solta os acompanhantes (companion_of = null) em vez de apagá-los.
    public function up(): void
    {
        if (Schema::hasColumn('trip_clients', 'companion_of')) {
            return;
        }

        Schema::table('trip_clients', function (Blueprint $table) {
            $table->unsignedBigInteger('companion_of')->nullable()->after('person_type')->index();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('trip_clients', 'companion_of')) {
            Schema::table('trip_clients', function (Blueprint $table) {
                $table->dropIndex(['companion_of']);
                $table->dropColumn('companion_of');
            });
        }
    }
};
