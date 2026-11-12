<?php

namespace App\Console\Commands;

use App\Models\OrdemServico;
use App\Services\OrdemServico\OrdemServicoNotificador;
use Illuminate\Console\Command;

class AvisarOsPrazoVencido extends Command
{
    protected $signature = 'os:avisar-prazos';

    protected $description = 'Avisa técnico e gestores das OS com prazo vencido (uma vez por OS)';

    public function handle(OrdemServicoNotificador $notificador): int
    {
        $n = 0;
        OrdemServico::whereNotIn('status', OrdemServico::ENCERRADAS)
            ->whereDate('prazo', '<', now()->toDateString())->whereNull('prazo_avisado_em')
            ->with('tecnico')->each(function (OrdemServico $os) use ($notificador, &$n) {
                $notificador->prazoVencido($os);
                $os->update(['prazo_avisado_em' => now()]);
                $n++;
            });
        $this->info("{$n} OS avisadas.");

        return self::SUCCESS;
    }
}
