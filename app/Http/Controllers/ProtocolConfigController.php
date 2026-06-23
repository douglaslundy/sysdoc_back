<?php

namespace App\Http\Controllers;

use App\Models\ProtocolConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocolConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(ProtocolConfig::current());
    }

    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'allow_external_protocols' => 'boolean',
            'allow_reopen' => 'boolean',
            'notify_internal' => 'boolean',
            'notify_email' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'default_priority' => 'nullable|string|max:20',
            'default_due_days' => 'nullable|integer|min:1|max:365',
            'evolution_base_url' => 'nullable|string|max:255',
            'evolution_api_key' => 'nullable|string',
            'evolution_default_session' => 'nullable|string|max:120',
            'evolution_enabled' => 'boolean',
            'observacoes' => 'nullable|string',
        ]);

        $config = ProtocolConfig::current();
        $config->update($request->only([
            'allow_external_protocols',
            'allow_reopen',
            'notify_internal',
            'notify_email',
            'notify_whatsapp',
            'default_priority',
            'default_due_days',
            'evolution_base_url',
            'evolution_api_key',
            'evolution_default_session',
            'evolution_enabled',
            'observacoes',
        ]));

        return response()->json($config->fresh());
    }
}
