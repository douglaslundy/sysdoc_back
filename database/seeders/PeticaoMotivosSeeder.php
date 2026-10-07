<?php

namespace Database\Seeders;

use App\Models\PeticaoMotivo;
use App\Models\ProtocolOrganizationalUnit;
use Illuminate\Database\Seeder;

/**
 * Motivos de petição pública, todos sob a unidade Vigilância Sanitária (a que recebe o card no
 * Kanban). Idempotente: pode ser rodado várias vezes; só preenche a unidade de motivo que ainda
 * não tem uma, sem sobrescrever o que foi ajustado na tela /peticao-motivos.
 *
 *   php artisan db:seed --class=PeticaoMotivosSeeder
 */
class PeticaoMotivosSeeder extends Seeder
{
    public function run(): void
    {
        $unit = ProtocolOrganizationalUnit::query()
            ->where('ativo', true)
            ->where('nome', 'like', '%Vigil%Sanit%')
            ->orderBy('id')
            ->first();

        if (! $unit) {
            $this->command?->warn('Unidade "Vigilância Sanitária" não encontrada em /protocolo/estrutura: motivos criados sem unidade. Cadastre a unidade e rode este seeder de novo.');
        }

        $motivos = [
            'Denúncia de estabelecimento irregular',
            'Solicitação de vistoria para renovação de alvará',
            'Solicitação de alvará',
        ];

        foreach ($motivos as $i => $nome) {
            $motivo = PeticaoMotivo::firstOrCreate(
                ['nome' => $nome],
                ['ativo' => true, 'ordem' => $i + 1, 'unit_id' => $unit?->id]
            );

            if ($unit && ! $motivo->unit_id) {
                $motivo->update(['unit_id' => $unit->id]);
            }
        }
    }
}
