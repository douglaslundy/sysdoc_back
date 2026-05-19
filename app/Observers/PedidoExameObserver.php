<?php

namespace App\Observers;

use App\Models\PedidoExame;
use App\Services\AuditService;

class PedidoExameObserver
{
    public function created(PedidoExame $pedido): void
    {
        AuditService::record('CREATE', $pedido, null, $pedido->toArray());
    }

    public function updated(PedidoExame $pedido): void
    {
        $dirty = $pedido->getDirty();
        $original = array_intersect_key($pedido->getOriginal(), $dirty);
        AuditService::record('UPDATE', $pedido, $original, $dirty);
    }

    public function deleted(PedidoExame $pedido): void
    {
        AuditService::record('DELETE', $pedido, $pedido->toArray(), null);
    }

    public function forceDeleted(PedidoExame $pedido): void
    {
        AuditService::record('DELETE', $pedido, $pedido->toArray(), null);
    }
}
