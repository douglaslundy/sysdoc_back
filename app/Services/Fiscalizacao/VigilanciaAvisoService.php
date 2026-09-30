<?php

namespace App\Services\Fiscalizacao;

use App\Models\Fiscalizacao;
use App\Models\VigilanciaContatoWhatsapp;
use App\Services\WhatsappEvolutionService;
use App\Support\AfterResponse;

/**
 * Avisa por WhatsApp os profissionais da Vigilância cadastrados (config da Vigilância) quando
 * chega uma denúncia ou é criada uma fiscalização interna. A mensagem NUNCA leva a identificação
 * do denunciante nem o texto da denúncia: os detalhes ficam dentro do sistema.
 */
class VigilanciaAvisoService
{
    public function __construct(
        private WhatsappEvolutionService $whatsapp,
        private FiscalizacaoTimeline $timeline
    ) {
    }

    public function notificarNova(Fiscalizacao $fiscalizacao): void
    {
        $contatos = VigilanciaContatoWhatsapp::query()->where('ativo', true)->get(['id', 'nome', 'telefone']);
        if ($contatos->isEmpty()) {
            return;
        }

        $mensagem = $this->mensagem($fiscalizacao);
        $fiscalizacaoId = $fiscalizacao->id;

        // Envio externo (timeout longo) fora da requisição; falha nunca desfaz o registro.
        AfterResponse::run(function () use ($contatos, $mensagem, $fiscalizacaoId) {
            $enviados = 0;
            $falhas = 0;

            foreach ($contatos as $contato) {
                try {
                    $resultado = $this->whatsapp->sendTextToNumber($contato->telefone, $mensagem);
                    ($resultado['ok'] ?? false) ? $enviados++ : $falhas++;
                } catch (\Throwable $e) {
                    $falhas++;
                    report($e);
                }
            }

            $fiscalizacao = Fiscalizacao::find($fiscalizacaoId);
            if ($fiscalizacao) {
                $this->timeline->registrar(
                    $fiscalizacao,
                    'aviso_whatsapp',
                    "Aviso por WhatsApp aos profissionais: {$enviados} enviado(s), {$falhas} falha(s).",
                    false,
                    null,
                    ['enviados' => $enviados, 'falhas' => $falhas]
                );
            }
        });
    }

    private function mensagem(Fiscalizacao $f): string
    {
        $link = rtrim((string) config('app.frontend_url'), '/').'/fiscalizacoes';

        if ($f->origem === 'denuncia') {
            return "🔔 Nova denúncia recebida\n"
                ."Protocolo: {$f->protocolo}\n"
                .'Assunto: '.($f->assunto ?: '—')."\n"
                .'Local: '.($f->local_endereco ?: '—')."\n"
                ."Acesse: {$link}";
        }

        $f->loadMissing('estabelecimento');

        return "📋 Nova fiscalização registrada\n"
            ."Protocolo: {$f->protocolo}\n"
            .'Estabelecimento: '.($f->estabelecimento?->nome_estabelecimento ?: '—')."\n"
            .'Situação: '.($f->resultado ?: '—')."\n"
            ."Acesse: {$link}";
    }
}
