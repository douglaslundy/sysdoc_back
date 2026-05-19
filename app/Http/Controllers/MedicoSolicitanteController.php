<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMedicoSolicitanteRequest;
use App\Models\MedicoSolicitante;
use App\Services\AuditService;
use Illuminate\Http\Request;

class MedicoSolicitanteController extends Controller
{
    public function index(Request $request)
    {
        $q = MedicoSolicitante::query();

        if ($request->filled('busca')) {
            $busca = $request->busca;
            $q->where(function ($sub) use ($busca) {
                $sub->where('nome', 'like', "%{$busca}%")
                    ->orWhere('crm', 'like', "%{$busca}%")
                    ->orWhere('especialidade', 'like', "%{$busca}%");
            });
        }

        if ($request->filled('ativo')) {
            $q->where('ativo', filter_var($request->ativo, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->boolean('all')) {
            return response()->json($q->orderBy('nome')->get());
        }

        return response()->json($q->orderBy('nome')->paginate($request->input('per_page', 15)));
    }

    public function store(StoreMedicoSolicitanteRequest $request)
    {
        $medico = MedicoSolicitante::create($request->validated());
        AuditService::record('CREATE', $medico, null, $medico->toArray());

        return response()->json($medico, 201);
    }

    public function show(MedicoSolicitante $medico)
    {
        return response()->json($medico);
    }

    public function update(StoreMedicoSolicitanteRequest $request, MedicoSolicitante $medico)
    {
        $old = $medico->toArray();
        $medico->update($request->validated());
        AuditService::record('UPDATE', $medico, $old, $medico->toArray());

        return response()->json($medico);
    }

    public function destroy(MedicoSolicitante $medico)
    {
        if ($medico->pedidos()->exists()) {
            return response()->json(['message' => 'Médico possui pedidos vinculados e não pode ser excluído.'], 422);
        }

        AuditService::record('DELETE', $medico, $medico->toArray(), null);
        $medico->delete();

        return response()->json(null, 204);
    }
}
