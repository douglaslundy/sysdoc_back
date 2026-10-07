<?php

namespace Database\Seeders;

use App\Models\PeticaoMotivo;
use App\Models\ProtocolOrganizationalUnit;
use Illuminate\Database\Seeder;

/**
 * Motivos de petição pública, todos sob a unidade Vigilância Sanitária (a que recebe o card no
 * Kanban). A unidade é localizada pelo nome (ativa ou não); se não existir, é criada. Idempotente:
 * pode ser rodado várias vezes; só preenche a unidade de motivo que ainda não tem uma, sem
 * sobrescrever o que foi ajustado na tela /peticao-motivos.
 *
 *   php artisan db:seed --class=PeticaoMotivosSeeder
 */
class PeticaoMotivosSeeder extends Seeder
{
    public function run(): void
    {
        $unit = $this->unidadeVigilancia();

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

    private function unidadeVigilancia(): ProtocolOrganizationalUnit
    {
        $unit = ProtocolOrganizationalUnit::query()
            ->where('nome', 'like', '%vigil%')
            ->where('nome', 'like', '%sanit%')
            ->orderByDesc('ativo')
            ->orderBy('id')
            ->first();

        if ($unit) {
            if (! $unit->ativo) {
                $this->command?->warn("Unidade \"{$unit->nome}\" (id {$unit->id}) está inativa; usada mesmo assim. Reative-a em /protocolo/estrutura.");
            }

            return $unit;
        }

        $secretaria = ProtocolOrganizationalUnit::query()
            ->where('tipo', 'secretaria')
            ->whereNull('parent_id')
            ->where('ativo', true)
            ->orderBy('id')
            ->first();

        $this->command?->info('Unidade "Vigilância Sanitária" não existia: criada em /protocolo/estrutura.');

        return ProtocolOrganizationalUnit::create([
            'parent_id' => $secretaria?->id,
            'tipo' => 'departamento',
            'codigo' => 'VISA',
            'nome' => 'Vigilância Sanitária',
            'ativo' => true,
        ]);
    }
}
