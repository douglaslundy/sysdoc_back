<?php

namespace App\Observers;

use App\Models\Client;
use App\Services\AuditService;

class ClientObserver
{
    public function created(Client $client): void
    {
        AuditService::record('CREATE', $client, null, $client->toArray());
    }

    public function updated(Client $client): void
    {
        $dirty = $client->getDirty();
        $original = array_intersect_key($client->getOriginal(), $dirty);
        AuditService::record('UPDATE', $client, $original, $dirty);
    }

    public function deleted(Client $client): void
    {
        AuditService::record('DELETE', $client, $client->toArray(), null);
    }
}
