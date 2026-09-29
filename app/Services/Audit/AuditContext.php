<?php

namespace App\Services\Audit;

use Illuminate\Database\Eloquent\Model;

/**
 * Descobre de qual cidadao (clients.id) um model trata, para que cada linha de
 * auditoria possa alimentar o "Historico do cidadao" com uma consulta indexada.
 */
final class AuditContext
{
    public static function clientIdFor(?Model $model): ?int
    {
        if ($model === null) {
            return null;
        }

        if (class_basename($model) === 'Client') {
            return (int) $model->getKey();
        }

        foreach (['client_id', 'id_client'] as $attribute) {
            $value = $model->getAttribute($attribute);
            if ($value) {
                return (int) $value;
            }
        }

        return match (class_basename($model)) {
            'ResultadoExame' => self::id($model->pedido?->client_id),
            'QueueAttachment' => self::id($model->queue?->id_client),
            'QueueTreatmentSession' => self::id($model->plan?->client_id),
            default => null,
        };
    }

    private static function id(mixed $value): ?int
    {
        return $value ? (int) $value : null;
    }
}
