<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageViewAuditController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path'    => ['required', 'string', 'max:255'],
            'label'   => ['nullable', 'string', 'max:100'],
            'filtros' => ['nullable', 'array'],
        ]);

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
