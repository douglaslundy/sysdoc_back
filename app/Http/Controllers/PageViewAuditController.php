<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePageViewAuditRequest;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageViewAuditController extends Controller
{
    public function store(StorePageViewAuditRequest $request): JsonResponse
    {
                $data = $request->validated();

        $hasFiltros = !empty($data['filtros']);
        $action     = $hasFiltros ? 'READ' : 'VIEW';

        $payload = array_filter([
            'event'   => $hasFiltros ? 'FILTER_CHANGE' : 'PAGE_VIEW',
            'path'    => $data['path'],
            'label'   => $data['label'] ?? null,
            'filtros' => $data['filtros'] ?? null,
        ], fn ($v) => $v !== null);

        AuditService::record($action, null, null, $payload);

        return response()->json(['ok' => true]);
    }
}
