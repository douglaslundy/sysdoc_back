<?php

namespace App\Services;

use App\Models\Alvara;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AlvaraNumberService
{
    /**
     * Gera o próximo número de alvará no formato XX-MM/AAAA.
     * Usa transação com lockForUpdate para garantir unicidade sob concorrência.
     */
    public static function gerar(string $dataAlvara): string
    {
        return DB::transaction(function () use ($dataAlvara) {
            $data = Carbon::parse($dataAlvara);
            $mes = $data->month;
            $ano = $data->year;
            $sufixo = sprintf('%02d/%04d', $mes, $ano);

            // Verifica duplicidade pelo proprio numero_alvara (o que a constraint unica
            // realmente protege), nao pela coluna data_alvara: ela pode ser editada depois
            // da criacao (update() nao altera numero_alvara), deixando o numero "orfao" do
            // seu mes/ano original e invisivel para uma busca por data_alvara.
            $sequencial = Alvara::withTrashed()
                ->where('numero_alvara', 'LIKE', "%-{$sufixo}")
                ->lockForUpdate()
                ->get(['numero_alvara'])
                ->map(function ($alvara) use ($sufixo) {
                    if (preg_match('/^(\d+)-'.preg_quote($sufixo, '/').'$/', (string) $alvara->numero_alvara, $matches) !== 1) {
                        return 0;
                    }

                    return (int) $matches[1];
                })
                ->max();

            $sequencial = ((int) $sequencial) + 1;

            return sprintf('%02d-%s', $sequencial, $sufixo);
        });
    }
}
