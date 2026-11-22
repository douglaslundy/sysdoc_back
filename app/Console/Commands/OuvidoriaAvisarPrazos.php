<?php

namespace App\Console\Commands;

use App\Models\OuvidoriaManifestacao;
use App\Services\Ouvidoria\OuvidoriaNotificador;
use Illuminate\Console\Command;

class OuvidoriaAvisarPrazos extends Command
{
    public const DIAS_ANTES = 5;

    protected $signature = 'ouvidoria:avisar-prazos';

    protected $description = 'Avisa o responsável (ou os ouvidores) das manifestações que vencem em poucos dias (uma vez por manifestação)';

    public function handle(OuvidoriaNotificador $notificador): int
    {
        $n = 0;
        OuvidoriaManifestacao::withoutSecretaria()
            ->whereNotIn('status', OuvidoriaManifestacao::FINAIS)
            ->where('prazo_em', '<=', now()->addDays(self::DIAS_ANTES))
            ->whereNull('prazo_avisado_em')
            // lazyById pagina pela chave: each() usa offset e pularia linhas que saem do filtro ao serem avisadas.
            ->lazyById(500)->each(function (OuvidoriaManifestacao $m) use ($notificador, &$n) {
                $notificador->prazoProximo($m);
                $m->forceFill(['prazo_avisado_em' => now()])->save();
                $n++;
            });
        $this->info("{$n} manifestações avisadas.");

        return self::SUCCESS;
    }
}
