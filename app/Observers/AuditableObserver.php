<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

/**
 * Observer generico: registra criacao, edicao (so os campos que mudaram) e exclusao.
 * Os models sao listados em config/audit.php.
 *
 * Os valores sao lidos ja convertidos pelos casts do model (arrays, campos criptografados
 * descriptografados em memoria) para que o AuditService consiga mascarar segredos por chave.
 */
class AuditableObserver
{
    public function created(Model $model): void
    {
        AuditService::record('CREATE', $model, null, $this->values($model, array_keys($model->getAttributes())));
    }

    public function updated(Model $model): void
    {
        $keys = $this->visibleKeys(array_keys($model->getChanges()));

        if ($keys === []) {
            return;
        }

        $old = [];
        foreach ($keys as $key) {
            $old[$key] = $model->getOriginal($key);
        }

        AuditService::record('UPDATE', $model, $old, $this->values($model, $keys));
    }

    public function deleted(Model $model): void
    {
        AuditService::record('DELETE', $model, $this->values($model, array_keys($model->getAttributes())), null);
    }

    /** @param  array<int, string>  $keys */
    private function values(Model $model, array $keys): array
    {
        $values = [];
        foreach ($this->visibleKeys($keys) as $key) {
            $values[$key] = $model->getAttribute($key);
        }

        return $values;
    }

    /** @param  array<int, string>  $keys */
    private function visibleKeys(array $keys): array
    {
        return array_values(array_diff($keys, config('audit.ignore_fields', [])));
    }
}
