<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServico;
use App\Models\User;
use App\Services\Alerts\MensagemDoSistema;
use App\Services\ConfiguredEmailService;
use App\Services\WhatsappEvolutionService;
use Illuminate\Support\Collection;
use Throwable;

/** Avisos da OS por WhatsApp e e-mail. Nunca lança; cada destinatário/canal é isolado. */
class OrdemServicoNotificador
{
    public function aberta(OrdemServico $os): void
    {
        $this->avisar($this->gestores($os), $os, 'os_aberta', 'os_aberta_gestor');
        $this->avisar($this->usuarios([$os->solicitante_id]), $os, 'os_aberta_solicitante', 'os_aberta_solicitante');
        if ($os->tecnico_id) {
            $this->tecnicoApontado($os);
        }
    }

    /** Técnico recebe "você foi designado"; o solicitante recebe quem vai atender (sem repetir para a mesma pessoa). */
    public function tecnicoApontado(OrdemServico $os): void
    {
        $this->avisar($this->usuarios([$os->tecnico_id]), $os, 'os_tecnico', 'os_tecnico');
        $this->avisar(
            $this->usuarios([$os->solicitante_id])->reject(fn ($u) => (int) $u->id === (int) $os->tecnico_id),
            $os, 'os_tecnico_solicitante', 'os_tecnico_solicitante'
        );
    }

    /** Concluída: o solicitante recebe o aviso direto; gestores e técnico recebem o aviso geral de status. */
    public function mudouStatus(OrdemServico $os, string $rotulo): void
    {
        $concluida = str_starts_with($rotulo, 'conclu');
        $destinos = $this->gestores($os)->merge($this->usuarios([$os->tecnico_id]));
        if (! $concluida) {
            $destinos = $destinos->merge($this->usuarios([$os->solicitante_id]));
        }
        $this->avisar($destinos->unique('id'), $os, 'os_status', 'os_status', ['os_status' => $rotulo]);

        if ($concluida) {
            $this->avisar($this->usuarios([$os->solicitante_id]), $os, 'os_concluida_solicitante', 'os_concluida_solicitante');
        }
    }

    public function prazoVencido(OrdemServico $os): void
    {
        $this->avisar($this->gestores($os)->merge($this->usuarios([$os->tecnico_id]))->unique('id'), $os, 'os_prazo', 'os_prazo_vencido');
    }

    /** Texto vem da tela Alertas (MensagemDoSistema); mensagem desativada lá não é enviada. */
    private function avisar(Collection $destinos, OrdemServico $os, string $origem, string $gatilho, array $extra = []): void
    {
        $mensagem = MensagemDoSistema::montar('os', $gatilho, [
            'os_numero' => $os->numero,
            'os_local' => $os->local,
            'os_tecnico' => $os->tecnico?->name,
            'os_prazo' => $os->prazo?->format('d/m/Y'),
        ] + $extra);

        if ($mensagem) {
            $this->enviar($destinos, $os, $origem, (string) $mensagem['assunto'], $mensagem['texto']);
        }
    }

    /** Gestores ativos da secretaria destino. */
    private function gestores(OrdemServico $os): Collection
    {
        return User::where('active', true)->withProfile('gestor')
            ->whereHas('secretarias', fn ($x) => $x->where('secretarias.id', $os->secretaria_id))->get();
    }

    private function usuarios(array $ids): Collection
    {
        return User::where('active', true)->whereIn('id', array_filter($ids))->get();
    }

    protected function enviar(Collection $destinos, OrdemServico $os, string $origem, string $assunto, string $texto): void
    {
        foreach ($destinos as $u) {
            $meta = ['origem' => $origem];
            $this->seguro(function () use ($u, $texto, $meta) {
                if ($u->whatsappPhoneNumber()) {
                    app(WhatsappEvolutionService::class)->sendTextToUser($u, $texto, $meta);
                }
            });
            $this->seguro(function () use ($u, $assunto, $texto, $meta) {
                if (trim((string) $u->email) !== '') {
                    app(ConfiguredEmailService::class)->sendTextToUser($u, $assunto, $texto, $meta);
                }
            });
        }
    }

    private function seguro(callable $fn): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            report($e);
        }
    }
}
