<?php

namespace App\Services\OrdemServico;

use App\Models\OrdemServico;
use App\Models\User;
use App\Services\ConfiguredEmailService;
use App\Services\WhatsappEvolutionService;
use Illuminate\Support\Collection;
use Throwable;

/** Avisos da OS por WhatsApp e e-mail. Nunca lança; cada destinatário/canal é isolado. */
class OrdemServicoNotificador
{
    public function aberta(OrdemServico $os): void
    {
        $this->enviar($this->gestores($os), $os, 'os_aberta', "Nova OS {$os->numero}", "Nova ordem de serviço {$os->numero} para a secretaria: {$os->local}.");
        $this->enviar($this->usuarios([$os->solicitante_id]), $os, 'os_aberta_solicitante', "OS {$os->numero} registrada", "Sua ordem de serviço {$os->numero} foi registrada.");
        if ($os->tecnico_id) {
            $this->tecnicoApontado($os);
        }
    }

    /** Técnico recebe "você foi designado"; o solicitante recebe quem vai atender (sem repetir para a mesma pessoa). */
    public function tecnicoApontado(OrdemServico $os): void
    {
        $this->enviar(
            $this->usuarios([$os->tecnico_id]), $os, 'os_tecnico', "OS {$os->numero} designada a você",
            "Você foi designado para a OS {$os->numero} ({$os->local}). Acesse o sistema para iniciar o atendimento."
        );
        $this->enviar(
            $this->usuarios([$os->solicitante_id])->reject(fn ($u) => (int) $u->id === (int) $os->tecnico_id),
            $os, 'os_tecnico_solicitante', "OS {$os->numero} atribuída",
            "A sua OS {$os->numero} ({$os->local}) foi atribuída ao técnico {$os->tecnico?->name}."
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
        $this->enviar($destinos->unique('id'), $os, 'os_status', "OS {$os->numero} {$rotulo}", "A OS {$os->numero} ({$os->local}) está {$rotulo}.");

        if ($concluida) {
            $this->enviar(
                $this->usuarios([$os->solicitante_id]), $os, 'os_concluida_solicitante', "Sua OS {$os->numero} foi concluída",
                "Sua ordem de serviço {$os->numero} ({$os->local}) foi concluída."
            );
        }
    }

    public function prazoVencido(OrdemServico $os): void
    {
        $this->enviar(
            $this->gestores($os)->merge($this->usuarios([$os->tecnico_id]))->unique('id'),
            $os, 'os_prazo', "OS {$os->numero} com prazo vencido", "A OS {$os->numero} ({$os->local}) passou do prazo ({$os->prazo?->format('d/m/Y')})."
        );
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
