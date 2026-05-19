<?php

namespace App\Observers;

use App\Models\ResultadoExame;
use App\Services\AuditService;

class ResultadoExameObserver
{
    public function created(ResultadoExame $resultado): void
    {
        AuditService::record('CREATE', $resultado, null, $resultado->toArray());
    }

    public function updated(ResultadoExame $resultado): void
    {
        $dirty = $resultado->getDirty();
        $original = array_intersect_key($resultado->getOriginal(), $dirty);
        AuditService::record('UPDATE', $resultado, $original, $dirty);
    }

    public function deleted(ResultadoExame $resultado): void
    {
        AuditService::record('DELETE', $resultado, $resultado->toArray(), null);
    }
}
