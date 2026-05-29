<?php

namespace App\Jobs;

use App\Models\SincronizacaoCidadao;
use App\Services\ConformidadeCidadaoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AplicarSincronizacaoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(public SincronizacaoCidadao $sync) {}

    public function handle(ConformidadeCidadaoService $service): void
    {
        $service->aplicar($this->sync);
    }

    public function failed(\Throwable $e): void
    {
        $this->sync->update([
            'status'        => 'failed',
            'erro_mensagem' => $e->getMessage(),
        ]);
    }
}
