<?php

use App\Support\IsolaPorSecretaria;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Filhas sem coluna própria (herdam o pai): attendance_calls, attendance_records (senha), queue_attachments,
     * queue_treatment_plans, qrcode_logs (fila), queue_treatment_sessions (plano -> fila), trip_clients (viagem),
     * user_speciality_permissions (especialidade), chat_messages (conversa), chat_attachments (mensagem -> conversa).
     * `chat_realtime_configs` e `chat_usage_daily` (cota do serviço de tempo real, compartilhado) seguem globais.
     * `clients`, `addresses` e `pessoa_fotos` também.
     */
    private const TABELAS = [
        'attendance_rooms', 'attendance_tickets', 'queue', 'public_queue_logs', 'painel_esus_presences',
        'trips', 'vehicles', 'routes', 'sectors', 'specialities', 'chat_conversations',
    ];

    /** tabela => lista de [colunas antigas, índice antigo, índice novo]; a unicidade passa a valer por secretaria. */
    private const UNICOS = [
        'attendance_rooms' => [[['name'], 'attendance_rooms_name_unique', 'attendance_rooms_sec_name_unique']],
        'attendance_tickets' => [
            [['sequence_date', 'number'], 'attendance_tickets_unique_daily_number', 'attendance_tickets_sec_daily_number'],
            [['sequence_date', 'display_code'], 'attendance_tickets_unique_daily_code', 'attendance_tickets_sec_daily_code'],
        ],
        'painel_esus_presences' => [[['cnes'], 'painel_esus_presences_cnes_unique', 'painel_esus_presences_sec_cnes_unique']],
        'vehicles' => [
            [['license_plate'], 'vehicles_license_plate_unique', 'vehicles_sec_license_plate_unique'],
            [['renavan'], 'vehicles_renavan_unique', 'vehicles_sec_renavan_unique'],
            [['chassis'], 'vehicles_chassis_unique', 'vehicles_sec_chassis_unique'],
        ],
    ];

    public function up(): void
    {
        IsolaPorSecretaria::up(self::TABELAS);

        foreach (self::UNICOS as $tabela => $indices) {
            foreach ($indices as [$colunas, $antigo, $novo]) {
                Schema::table($tabela, fn (Blueprint $t) => $t->dropUnique($antigo));
                Schema::table($tabela, fn (Blueprint $t) => $t->unique(['secretaria_id', ...$colunas], $novo));
            }
        }
    }

    public function down(): void
    {
        foreach (self::UNICOS as $tabela => $indices) {
            // O índice composto sustenta a FK de secretaria_id: solta a FK antes de trocar os índices.
            IsolaPorSecretaria::dropFk($tabela);
            foreach ($indices as [$colunas, $antigo, $novo]) {
                Schema::table($tabela, fn (Blueprint $t) => $t->dropUnique($novo));
                Schema::table($tabela, fn (Blueprint $t) => $t->unique($colunas, $antigo));
            }
        }

        IsolaPorSecretaria::down(self::TABELAS);
    }
};
