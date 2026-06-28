<?php

namespace App\Services;

use App\Models\ProtocolAlert;
use App\Models\User;
use Illuminate\Support\Collection;

class SystemAlertService
{
    public function __construct(
        private readonly WhatsappEvolutionService $whatsapp,
        private readonly ConfiguredEmailService $email,
    ) {
    }

    public function dispatch(string $module, string $trigger, array $context = []): void
    {
        $alerts = ProtocolAlert::query()
            ->where('ativo', true)
            ->where('modulo', $module)
            ->where('gatilho', $trigger)
            ->orderBy('id')
            ->get();

        foreach ($alerts as $alert) {
            $recipients = $this->resolveRecipients((array) $alert->destinatarios, $context);
            if ($recipients->isEmpty()) {
                continue;
            }

            $message = $this->renderTemplate($alert->template, $module, $trigger, $context);
            $subject = $this->subjectLine($alert->nome, $module, $trigger, $context);

            foreach ($recipients as $recipient) {
                foreach ((array) $alert->canais as $channel) {
                    if ($channel === 'whatsapp') {
                        $this->whatsapp->sendTextToUser($recipient, $message);
                    }

                    if ($channel === 'email') {
                        $this->email->sendTextToUser($recipient, $subject, $message);
                    }
                }
            }
        }
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
        ];

        $fallback = match ($trigger) {
            'solicitacao_exclusao_pendente' => sprintf(
                'Existe uma solicitacao pendente de exclusao para o documento %s. Acesse %s para analisar.',
                (string) ($document?->titulo ?? ('#' . ($document?->id ?? ''))),
                '/documentos/aprovacoes'
            ),
            'solicitacao_exclusao_aprovada' => sprintf(
                'A solicitacao de exclusao do documento %s foi aprovada.',
                (string) ($document?->titulo ?? ('#' . ($document?->id ?? '')))
            ),
            'solicitacao_exclusao_rejeitada' => sprintf(
                'A solicitacao de exclusao do documento %s foi rejeitada.',
                (string) ($document?->titulo ?? ('#' . ($document?->id ?? '')))
            ),
            'documento_cadastrado' => sprintf(
                'O documento %s foi cadastrado.',
                (string) ($document?->titulo ?? ('#' . ($document?->id ?? '')))
            ),
            'documento_atualizado' => sprintf(
                'O documento %s foi atualizado.',
                (string) ($document?->titulo ?? ('#' . ($document?->id ?? '')))
            ),
            'documento_publicado' => sprintf(
                'O documento %s foi publicado.',
                (string) ($document?->titulo ?? ('#' . ($document?->id ?? '')))
            ),
            'requisicao_criada' => sprintf(
                'A requisicao %s foi criada para %s.',
                (string) ($requisicao?->numero ?? ''),
                (string) ($requisicao?->secretaria?->nome ?? 'almoxarifado')
            ),
            'requisicao_em_analise' => sprintf(
                'A requisicao %s entrou em analise.',
                (string) ($requisicao?->numero ?? '')
            ),
            'requisicao_aprovada' => sprintf(
                'A requisicao %s foi aprovada.',
                (string) ($requisicao?->numero ?? '')
            ),
            'requisicao_recusada' => sprintf(
                'A requisicao %s foi recusada.',
                (string) ($requisicao?->numero ?? '')
            ),
            'requisicao_em_separacao' => sprintf(
                'A requisicao %s entrou em separacao.',
                (string) ($requisicao?->numero ?? '')
            ),
            'requisicao_em_processo_de_entrega' => sprintf(
                'A requisicao %s esta em processo de entrega.',
                (string) ($requisicao?->numero ?? '')
            ),
            'requisicao_entregue' => sprintf(
                'A requisicao %s foi entregue.',
                (string) ($requisicao?->numero ?? '')
            ),
            'requisicao_cancelada' => sprintf(
                'A requisicao %s foi cancelada.',
                (string) ($requisicao?->numero ?? '')
            ),
            'kanban_item_criado' => sprintf(
                'O item do kanban %s foi criado.',
                (string) ($kanbanTask?->titulo ?? '')
            ),
            'kanban_item_atualizado' => sprintf(
                'O item do kanban %s foi atualizado.',
                (string) ($kanbanTask?->titulo ?? '')
            ),
            'kanban_status_alterado' => sprintf(
                'O item do kanban %s mudou para %s.',
                (string) ($kanbanTask?->titulo ?? ''),
                (string) ($kanbanTask?->status ?? '')
            ),
            'oficio_cadastrado' => sprintf(
                'O oficio %s foi cadastrado.',
                (string) ($letter?->number ?? '')
            ),
            'oficio_atualizado' => sprintf(
                'O oficio %s foi atualizado.',
                (string) ($letter?->number ?? '')
            ),
            'oficio_protocolo_criado' => sprintf(
                'Foi criado um protocolo a partir do oficio %s.',
                (string) ($letter?->number ?? '')
            ),
            'chat_conversa_iniciada' => 'Uma nova conversa de chat foi iniciada.',
            'chat_mensagem_enviada' => 'Uma nova mensagem de chat foi enviada.',
            default => sprintf('Alerta %s disparado para o modulo %s.', $trigger, $module),
        };

        $content = trim((string) $template) !== '' ? (string) $template : $fallback;

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    private function subjectLine(string $name, string $module, string $trigger, array $context): string
    {
        $document = $context['document'] ?? null;
        $requisicao = $context['almoxarifado_requisicao'] ?? null;
        $kanbanTask = $context['kanban_task'] ?? null;
        $letter = $context['letter'] ?? null;

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
