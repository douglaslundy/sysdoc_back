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

            $sequencial = Alvara::withTrashed()
                ->whereYear('data_alvara', $ano)
                ->whereMonth('data_alvara', $mes)
                ->lockForUpdate()
                ->get(['numero_alvara'])
                ->map(function ($alvara) {
                    if (preg_match('/^(\d+)-/', (string) $alvara->numero_alvara, $matches) !== 1) {
                        return 0;
                    }

                    return (int) $matches[1];
                })
                ->max();

            $sequencial = ((int) $sequencial) + 1;

            return sprintf('%02d-%02d/%04d', $sequencial, $mes, $ano);
        });
    }
}
