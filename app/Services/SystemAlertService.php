<?php

namespace App\Services;

use App\Models\ProtocolAlert;
use App\Models\User;
use App\Services\Secretarias\SecretariaMembership;
use App\Support\AfterResponse;
use Illuminate\Support\Collection;

class SystemAlertService
{
    public function __construct(
        private readonly WhatsappEvolutionService $whatsapp,
        private readonly ConfiguredEmailService $email,
    ) {
    }

    /**
     * Dispara os alertas depois da resposta HTTP: cada canal (WhatsApp/e-mail)
     * faz chamada externa lenta e nao pode segurar o usuario.
     */
    public function dispatch(string $module, string $trigger, array $context = []): void
    {
        // Pedido de cadastro (dados pessoais no título/assunto): nenhum alerta, de nenhum módulo ou gatilho.
        foreach (['protocol', 'kanban_task'] as $chave) {
            if (($context[$chave] ?? null)?->isCadastro()) {
                return;
            }
        }

        AfterResponse::run(fn () => $this->deliver($module, $trigger, $context));
    }

    private function deliver(string $module, string $trigger, array $context): void
    {
        $alerts = ProtocolAlert::query()
            ->where('ativo', true)
            ->where('modulo', $module)
            ->where('gatilho', $trigger)
            ->orderBy('id')
            ->get();

        foreach ($alerts as $alert) {
            $recipients = $this->resolveRecipients((array) $alert->destinatarios, $context);
            // O alerta carrega título/assunto do registro: só vai a quem atende a secretaria dele.
            if ($secretariaId = $this->contextSecretariaId($context)) {
                $protocolo = $context['protocol'] ?? null;
                // Quem recebeu o protocolo (de outra secretaria) também é avisado: já pode vê-lo.
                $recipients = $recipients->filter(fn (User $u) => SecretariaMembership::atende((int) $u->id, $secretariaId)
                    || ($protocolo instanceof \App\Models\Protocol && \App\Support\ParticipacaoProtocolo::participa($protocolo, (int) $u->id)))->values();
            }
            if ($recipients->isEmpty()) {
                continue;
            }

            $message = $this->renderTemplate($alert->template, $module, $trigger, $context);
            $subject = $this->subjectLine($alert->nome, $module, $trigger, $context);

            $meta = ['origem' => "{$module}:{$trigger}"];

            foreach ($recipients as $recipient) {
                foreach ((array) $alert->canais as $channel) {
                    if ($channel === 'whatsapp') {
                        $this->whatsapp->sendTextToUser($recipient, $message, $meta);
                    }

                    if ($channel === 'email') {
                        $this->email->sendTextToUser($recipient, $subject, $message, $meta);
                    }
                }
            }
        }
    }

    private function contextSecretariaId(array $context): ?int
    {
        foreach (['document', 'letter', 'protocol', 'kanban_task', 'ordinance', 'almoxarifado_requisicao'] as $chave) {
            $id = $context[$chave]->secretaria_id ?? null;
            if ($id) {
                return (int) $id;
            }
        }

        return null;
    }

    private function resolveRecipients(array $recipientKeys, array $context): Collection
    {
        $users = collect();

        foreach ($recipientKeys as $key) {
            $key = trim((string) $key);

            if ($key === 'assinantes_exclusao_documento') {
                $users = $users->merge($context['signers'] ?? []);
            }

            if ($key === 'solicitante_documento' && ($context['requester'] ?? null) instanceof User) {
                $users->push($context['requester']);
            }

            if ($key === 'criador_documento' && ($context['document']?->creator ?? null) instanceof User) {
                $users->push($context['document']->creator);
            }

            if ($key === 'administrador') {
                $users = $users->merge(
                    User::query()->where('active', true)->where('profile', 'admin')->get()
                );
            }

            if ($key === 'solicitante_almoxarifado' && ($context['requester'] ?? null) instanceof User) {
                $users->push($context['requester']);
            }

            if ($key === 'responsavel_almoxarifado' && ($context['almoxarifado_requisicao']?->responsavel ?? null) instanceof User) {
                $users->push($context['almoxarifado_requisicao']->responsavel);
            }

            if ($key === 'aprovadores_almoxarifado') {
                $users = $users->merge(
                    User::query()
                        ->where('active', true)
                        ->get()
                        ->filter(fn (User $user) => $user->canUseAlmoxarifadoAction('approve'))
                        ->values()
                );
            }

            if ($key === 'entregadores_almoxarifado') {
                $users = $users->merge(
                    User::query()
                        ->where('active', true)
                        ->get()
                        ->filter(fn (User $user) => $user->canUseAlmoxarifadoAction('deliver'))
                        ->values()
                );
            }

            if ($key === 'criador_kanban' && ($context['kanban_task']?->createdBy ?? null) instanceof User) {
                $users->push($context['kanban_task']->createdBy);
            }

            if ($key === 'responsavel_kanban' && ($context['kanban_task']?->responsavel ?? null) instanceof User) {
                $users->push($context['kanban_task']->responsavel);
            }

            if ($key === 'criador_oficio' && ($context['letter']?->user ?? null) instanceof User) {
                $users->push($context['letter']->user);
            }

            if ($key === 'destinatario_protocolo_oficio' && ($context['destination_user'] ?? null) instanceof User) {
                $users->push($context['destination_user']);
            }

            if ($key === 'remetente_chat' && ($context['sender'] ?? null) instanceof User) {
                $users->push($context['sender']);
            }

            if ($key === 'destinatario_chat' && ($context['recipient'] ?? null) instanceof User) {
                $users->push($context['recipient']);
            }

            if ($key === 'participantes_chat') {
                $users = $users->merge($context['participants'] ?? []);
            }
        }

        return $users
            ->filter(fn ($user) => $user instanceof User)
            ->unique(fn (User $user) => (int) $user->id)
            ->values();
    }

    private function renderTemplate(?string $template, string $module, string $trigger, array $context): string
    {
        $document = $context['document'] ?? null;
        $requisicao = $context['almoxarifado_requisicao'] ?? null;
        $kanbanTask = $context['kanban_task'] ?? null;
        $letter = $context['letter'] ?? null;
        $conversation = $context['conversation'] ?? null;
        $requester = $context['requester'] ?? null;
        $protocol = $context['protocol'] ?? null;
        $actor = $context['actor'] ?? null;

        $replacements = [
            '{{modulo}}' => $module,
            '{{gatilho}}' => $trigger,
            '{{documento_id}}' => (string) ($document?->id ?? ''),
            '{{documento_titulo}}' => (string) ($document?->titulo ?? ''),
            '{{documento_sigilo}}' => (string) ($document?->sigilo ?? ''),
            '{{solicitante}}' => (string) ($requester?->name ?? ''),
            '{{link_aprovacoes_documentos}}' => '/documentos/aprovacoes',
            '{{requisicao_numero}}' => (string) ($requisicao?->numero ?? ''),
            '{{requisicao_status}}' => (string) ($requisicao?->status ?? ''),
            '{{requisicao_secretaria}}' => (string) ($requisicao?->secretaria?->nome ?? ''),
            '{{link_requisicoes_almoxarifado}}' => '/almoxarifado/requisicoes',
            '{{kanban_titulo}}' => (string) ($kanbanTask?->titulo ?? ''),
            '{{kanban_status}}' => (string) ($kanbanTask?->status ?? ''),
            '{{link_kanban}}' => '/kanban',
            '{{oficio_numero}}' => (string) ($letter?->number ?? ''),
            '{{oficio_assunto}}' => (string) ($letter?->subject_matter ?? ''),
            '{{link_oficios}}' => '/letters',
            '{{chat_conversa_id}}' => (string) ($conversation?->id ?? ''),
            '{{chat_mensagem}}' => (string) (($context['message']?->body ?? '') ?: ''),
            '{{link_chat}}' => '/dashboard',
            '{{protocolo_numero}}' => (string) ($protocol?->numero ?? ''),
            '{{protocolo_assunto}}' => (string) ($protocol?->assunto ?? ''),
            '{{protocolo_status}}' => (string) ($protocol?->status ?? ''),
            '{{protocolo_autor}}' => (string) ($actor?->name ?? ''),
            '{{link_protocolos}}' => '/protocolo',
        ];

        // Sem texto na tela Alertas: vale o padrão do catálogo (config/alert_catalog.php), nunca texto fixo aqui.
        $fallback = (string) config("alert_catalog.padroes.{$trigger}", config('alert_catalog.padroes_gerais'));

        $content = trim((string) $template) !== '' ? (string) $template : $fallback;

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    private function subjectLine(string $name, string $module, string $trigger, array $context): string
    {
        $document = $context['document'] ?? null;
        $requisicao = $context['almoxarifado_requisicao'] ?? null;
        $kanbanTask = $context['kanban_task'] ?? null;
        $letter = $context['letter'] ?? null;
        $protocol = $context['protocol'] ?? null;

        if ($module === 'protocolo' && $protocol?->numero) {
            return sprintf('%s: %s', $name, $protocol->numero);
        }

        if ($module === 'documentos' && $document?->titulo) {
            return sprintf('%s: %s', $name, $document->titulo);
        }

        if ($module === 'almoxarifado' && $requisicao?->numero) {
            return sprintf('%s: %s', $name, $requisicao->numero);
        }

        if ($module === 'kanban' && $kanbanTask?->titulo) {
            return sprintf('%s: %s', $name, $kanbanTask->titulo);
        }

        if ($module === 'oficios' && $letter?->number) {
            return sprintf('%s: Oficio %s', $name, $letter->number);
        }

        return trim($name) !== '' ? $name : sprintf('Alerta %s/%s', $module, $trigger);
    }
}
