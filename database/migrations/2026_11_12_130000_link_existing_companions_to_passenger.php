<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Acompanhantes antigos (sem vínculo) passam a seguir o passageiro da viagem, mas só quando a
    // viagem tem exatamente 1 passageiro: com mais de um não há como saber de quem é cada acompanhante.
    // Só preenche companion_of nulo, então rodar de novo não muda nada.
    public function up(): void
    {
        DB::statement("
            UPDATE trip_clients c
            JOIN (
                SELECT trip_id, MIN(id) AS passageiro_id
                FROM trip_clients
                WHERE person_type = 'passenger'
                GROUP BY trip_id
                HAVING COUNT(*) = 1
            ) p ON p.trip_id = c.trip_id
            SET c.companion_of = p.passageiro_id
            WHERE c.person_type = 'companion' AND c.companion_of IS NULL
        ");
    }

    // Não desfaz: depois de rodar não dá para distinguir vínculo automático de manual.
    public function down(): void
    {
    }
};
