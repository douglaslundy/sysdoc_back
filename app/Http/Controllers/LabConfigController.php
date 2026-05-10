<?php

namespace App\Http\Controllers;

use App\Models\LabConfig;
use App\Services\AuditService;
use Illuminate\Http\Request;

class LabConfigController extends Controller
{
    public function show()
    {
        return response()->json(LabConfig::get());
    }

    public function update(Request $request)
    {
        $request->validate([
            'email_habilitado' => 'required|boolean',
        ]);

        $config = LabConfig::get();
        $old = $config->toArray();
        $config->update(['email_habilitado' => $request->boolean('email_habilitado')]);
        AuditService::record('UPDATE', $config, $old, $config->fresh()->toArray());

        return response()->json($config->fresh());
    }
}
