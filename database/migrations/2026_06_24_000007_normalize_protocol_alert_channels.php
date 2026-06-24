<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $alerts = DB::table('protocol_alerts')->get(['id', 'canais']);

        foreach ($alerts as $alert) {
            $channels = json_decode((string) ($alert->canais ?? '[]'), true);
            $channels = is_array($channels) ? $channels : [];

            $channels = array_values(array_filter(
                array_map(static fn ($value) => trim((string) $value), $channels),
                static fn ($value) => in_array($value, ['whatsapp', 'email'], true)
            ));

            if ($channels === []) {
                $channels = ['whatsapp'];
            }

            DB::table('protocol_alerts')
                ->where('id', $alert->id)
                ->update([
                    'canais' => json_encode($channels),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Sem rollback seguro dos canais antigos.
    }
};
