<?php

namespace App\Http\Controllers;

use App\Models\ProtocolConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProtocolConfigController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->payload(ProtocolConfig::current()));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'allow_external_protocols' => 'boolean',
            'allow_reopen' => 'boolean',
            'notify_whatsapp' => 'boolean',
            'default_priority' => 'nullable|string|max:20',
            'default_due_days' => 'nullable|integer|min:1|max:365',
            'observacoes' => 'nullable|string',
        ]);

        $config = ProtocolConfig::current();
        $config->update([
            'allow_external_protocols' => $request->boolean('allow_external_protocols', $config->allow_external_protocols),
            'allow_reopen' => $request->boolean('allow_reopen', $config->allow_reopen),
            'notify_whatsapp' => $request->boolean('notify_whatsapp', $config->notify_whatsapp),
            'default_priority' => $validated['default_priority'] ?? $config->default_priority,
            'default_due_days' => $validated['default_due_days'] ?? $config->default_due_days,
            'observacoes' => $validated['observacoes'] ?? $config->observacoes,
        ]);

        return response()->json($this->payload($config->fresh()));
    }

    private function payload(ProtocolConfig $config): array
    {
        return [
            'allow_external_protocols' => (bool) $config->allow_external_protocols,
            'allow_reopen' => (bool) $config->allow_reopen,
            'notify_whatsapp' => (bool) $config->notify_whatsapp,
            'default_priority' => (string) ($config->default_priority ?? 'normal'),
            'default_due_days' => (int) ($config->default_due_days ?? 5),
            'observacoes' => (string) ($config->observacoes ?? ''),
        ];
    }
}
