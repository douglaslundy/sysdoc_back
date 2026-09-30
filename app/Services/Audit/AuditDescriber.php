<?php

namespace App\Services\Audit;

use App\Models\AuditLog;

/**
 * Converte uma linha bruta de auditoria em texto para pessoas (tela de histórico).
 * Não faz consulta: trabalha só com o que já está na linha.
 */
final class AuditDescriber
{
    private const MAX_FIELDS = 6;

    private const MAX_VALUE = 60;

    private const HIDDEN_FIELDS = ['__audit_subject_name'];

    private const FIELD_LABELS = [
        'name' => 'Nome', 'mother' => 'Mãe', 'cpf' => 'CPF', 'cns' => 'CNS', 'phone' => 'Telefone',
        'email' => 'E-mail', 'born_date' => 'Nascimento', 'active' => 'Ativo', 'sex' => 'Sexo',
        'done' => 'Baixa', 'obs' => 'Observação', 'urgency' => 'Urgência', 'date_of_realized' => 'Data de realização',
        'id_specialities' => 'Especialidade', 'is_confirmed' => 'Confirmado', 'person_type' => 'Tipo de pessoa',
        'departure_location' => 'Local de saída', 'destination_location' => 'Local de destino', 'time' => 'Horário',
        'data_obito' => 'Data do óbito', 'status' => 'Status',
    ];

    private const MODEL_LABELS = [
        'Client' => 'cadastro do cidadão', 'Queue' => 'item da fila', 'TripClient' => 'passageiro de viagem',
        'PedidoExame' => 'pedido de exame', 'ResultadoExame' => 'resultado de exame', 'Addresses' => 'endereço',
        'QueueTreatmentPlan' => 'plano de tratamento', 'QueueTreatmentSession' => 'sessão de tratamento',
    ];

    /** @return array{titulo: string, detalhe: ?string} */
    public static function describe(AuditLog $log): array
    {
        return ['titulo' => self::title($log), 'detalhe' => self::detail($log)];
    }

    private static function title(AuditLog $log): string
    {
        $action = (string) $log->action;
        $model = (string) $log->model_type;
        $old = (array) $log->old_values;
        $new = (array) $log->new_values;

        return match (true) {
            $action === 'VIEW' && $model === 'Client' => 'Visualizou o cadastro',
            $action === 'VIEW_REPORT' => 'Consultou o relatório do cidadão',
            $action === 'CREATE' && $model === 'Client' => 'Cadastrou o cidadão',
            $action === 'UPDATE' && $model === 'Client' => 'Editou o cadastro',
            $action === 'DELETE' && $model === 'Client' => 'Excluiu o cadastro',

            $action === 'CREATE' && $model === 'Queue' => 'Inseriu na fila',
            $action === 'UPDATE' && $model === 'Queue' && self::flipped($old, $new, 'done') === true => 'Deu baixa na fila',
            $action === 'UPDATE' && $model === 'Queue' && self::flipped($old, $new, 'done') === false => 'Reabriu item da fila',
            $action === 'UPDATE' && $model === 'Queue' => 'Editou item da fila',
            $action === 'DELETE' && $model === 'Queue' => 'Removeu da fila',
            $action === 'VIEW' && $model === 'Queue' => 'Visualizou item da fila',
            $action === 'CREATE_ATTACHMENT' => 'Anexou arquivo na fila',
            $action === 'DOWNLOAD_ATTACHMENT' => 'Baixou anexo da fila',
            $action === 'DELETE_ATTACHMENT' => 'Excluiu anexo da fila',
            $action === 'SCHEDULE_SESSIONS' => 'Agendou sessões de tratamento',
            $action === 'RETURN_TO_QUEUE' => 'Devolveu à fila',

            $action === 'CREATE' && $model === 'TripClient' => 'Inseriu em viagem',
            $action === 'DELETE' && $model === 'TripClient' => 'Removeu de viagem',
            $action === 'UPDATE' && $model === 'TripClient' && self::flipped($old, $new, 'is_confirmed') === true => 'Confirmou viagem',
            $action === 'UPDATE' && $model === 'TripClient' && self::flipped($old, $new, 'is_confirmed') === false => 'Desfez a confirmação da viagem',
            $action === 'UPDATE' && $model === 'TripClient' => 'Editou passageiro da viagem',

            $action === 'CREATE' && $model === 'PedidoExame' => 'Solicitou exame',
            $action === 'VIEW' && $model === 'PedidoExame' => 'Visualizou pedido de exame',
            $action === 'VIEW' && $model === 'ResultadoExame' => 'Visualizou resultado de exame',

            in_array($action, ['CREATE', 'UPDATE', 'DELETE', 'VIEW'], true) && isset(self::MODEL_LABELS[$model])
                => self::verb($action).' '.self::MODEL_LABELS[$model],

            default => ucfirst(str_replace('_', ' ', strtolower($action))),
        };
    }

    private static function verb(string $action): string
    {
        return match ($action) {
            'CREATE' => 'Criou', 'UPDATE' => 'Editou', 'DELETE' => 'Excluiu', default => 'Visualizou',
        };
    }

    /** true = passou a verdadeiro, false = passou a falso, null = sem mudança nesse campo. */
    private static function flipped(array $old, array $new, string $field): ?bool
    {
        if (! array_key_exists($field, $new)) {
            return null;
        }

        $before = (bool) ($old[$field] ?? false);
        $after = (bool) $new[$field];

        return $before === $after ? null : $after;
    }

    private static function detail(AuditLog $log): ?string
    {
        $old = (array) $log->old_values;
        $new = (array) $log->new_values;

        if ($old === [] || $new === []) {
            return null;
        }

        $lines = [];
        foreach ($new as $field => $after) {
            if (in_array($field, self::HIDDEN_FIELDS, true) || ! array_key_exists($field, $old)) {
                continue;
            }
            $lines[] = (self::FIELD_LABELS[$field] ?? $field).': '.self::value($old[$field]).' → '.self::value($after);
        }

        if ($lines === []) {
            return null;
        }

        $extra = count($lines) - self::MAX_FIELDS;
        $lines = array_slice($lines, 0, self::MAX_FIELDS);
        if ($extra > 0) {
            $lines[] = "… e mais {$extra} campo(s)";
        }

        return implode("\n", $lines);
    }

    private static function value(mixed $value): string
    {
        $text = match (true) {
            $value === null || $value === '' => '—',
            $value === true => 'sim',
            $value === false => 'não',
            is_array($value) => (string) json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };

        return mb_strlen($text) > self::MAX_VALUE ? mb_substr($text, 0, self::MAX_VALUE).'…' : $text;
    }
}
