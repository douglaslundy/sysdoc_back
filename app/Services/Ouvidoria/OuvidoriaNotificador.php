<?php

namespace App\Services\Ouvidoria;

use App\Models\OuvidoriaManifestacao;
use App\Models\User;
use App\Services\ConfiguredEmailService;
use App\Services\WhatsappEvolutionService;
use Illuminate\Support\Collection;
use Throwable;

/** Avisos da ouvidoria por WhatsApp e e-mail. Nunca lança; cada destinatário/canal é isolado. */
class OuvidoriaNotificador
{
    public function registrada(OuvidoriaManifestacao $m): void
    {
        $this->paraUsuarios($this->ouvidores($m->secretaria_id), $m, 'ouvidoria_registrada',
            'Nova manifestação na Ouvidoria', "Chegou a manifestação {$m->protocolo} ({$this->tipo($m)}): {$m->assunto}.");
    }

    /** Só o cidadão identificado com contato recebe; o anônimo nunca é avisado. */
    public function respondida(OuvidoriaManifestacao $m): void
    {
        if ($m->anonimo) {
            return;
        }
        $assunto = 'Sua manifestação na Ouvidoria foi respondida';
        $texto = "Olá! A sua manifestação {$m->protocolo} foi respondida. Consulte a resposta com o protocolo e a senha que você recebeu ao registrar.";
        $meta = ['origem' => 'ouvidoria_respondida'];

        $numero = (new User(['phone' => $m->phone]))->whatsappPhoneNumber();
        if ($numero) {
            $this->seguro(fn () => app(WhatsappEvolutionService::class)->sendTextToNumber($numero, $texto, $meta));
        }
        if (trim((string) $m->email) !== '') {
            $this->seguro(fn () => app(ConfiguredEmailService::class)->sendText((string) $m->email, $assunto, $texto, null, null, $meta));
        }
    }

    public function encaminhada(OuvidoriaManifestacao $m): void
    {
        if (! $m->responsavel_id) {
            return;
        }
        $this->paraUsuarios(User::whereKey($m->responsavel_id)->get(), $m, 'ouvidoria_encaminhada',
            'Manifestação encaminhada a você', "A manifestação {$m->protocolo} ({$this->tipo($m)}) foi encaminhada a você: {$m->assunto}.");
    }

    public function prazoProximo(OuvidoriaManifestacao $m): void
    {
        $destinos = $m->responsavel_id ? User::whereKey($m->responsavel_id)->get() : $this->ouvidores($m->secretaria_id);
        $this->paraUsuarios($destinos, $m, 'ouvidoria_prazo',
            'Prazo da Ouvidoria perto de vencer', "A manifestação {$m->protocolo} vence em {$m->prazo_em?->format('d/m/Y')}: {$m->assunto}.");
    }

    private function tipo(OuvidoriaManifestacao $m): string
    {
        return mb_strtolower(OuvidoriaManifestacao::TIPOS[$m->tipo] ?? $m->tipo);
    }

    /** Ouvidores de uma secretaria (admins ficam de fora para não receberem tudo). */
    private function ouvidores(int $secretariaId): Collection
    {
        $dest = new OuvidoriaDestinatarios();

        return $dest->elegiveis()->filter(fn (User $u) => $u->profile !== 'admin' && $dest->valido($u, $secretariaId));
    }

    private function paraUsuarios(Collection $usuarios, OuvidoriaManifestacao $m, string $origem, string $assunto, string $texto): void
    {
        foreach ($usuarios as $u) {
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
