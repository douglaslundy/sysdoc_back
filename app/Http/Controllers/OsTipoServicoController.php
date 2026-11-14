<?php

namespace App\Http\Controllers;

use App\Http\Requests\OsConfiguracaoRequest;
use App\Http\Requests\OsTipoServicoRequest;
use App\Models\OsConfiguracao;
use App\Models\OsTipoServico;
use App\Models\Scopes\CadastroProtocoloScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OsTipoServicoController extends Controller
{
    /** Tipos da secretaria destino. Com ?todos=1 o gestor/admin vê também os inativos. */
    public function index(Request $request): JsonResponse
    {
        $secretaria = (int) $request->query('secretaria_id');
        $todos = $request->boolean('todos') && CadastroProtocoloScope::pode($request->user(), $secretaria);

        return response()->json(
            OsTipoServico::where('secretaria_id', $secretaria)->when(! $todos, fn ($q) => $q->where('ativo', true))->orderBy('nome')->get()
        );
    }

    public function store(OsTipoServicoRequest $request): JsonResponse
    {
        if (! CadastroProtocoloScope::pode($request->user(), (int) $request->secretaria_id)) {
            return response()->json(['message' => 'Apenas o gestor da secretaria cadastra tipos de serviço.'], 403);
        }
        if (OsTipoServico::where('secretaria_id', $request->secretaria_id)->where('nome', $request->nome)->exists()) {
            return response()->json(['message' => 'Já existe um tipo com este nome.'], 422);
        }

        return response()->json(OsTipoServico::create($request->validated()), 201);
    }

    public function update(OsTipoServicoRequest $request, int $id): JsonResponse
    {
        $tipo = OsTipoServico::findOrFail($id);
        if (! CadastroProtocoloScope::pode($request->user(), $tipo->secretaria_id)) {
            return response()->json(['message' => 'Apenas o gestor da secretaria edita tipos de serviço.'], 403);
        }
        if (OsTipoServico::where('secretaria_id', $tipo->secretaria_id)->where('nome', $request->nome)->where('id', '!=', $id)->exists()) {
            return response()->json(['message' => 'Já existe um tipo com este nome.'], 422);
        }
        $tipo->update($request->validated());

        return response()->json($tipo);
    }

    public function configuracao(Request $request): JsonResponse
    {
        abort_unless($request->user()->profile === 'admin', 403);

        return response()->json(OsConfiguracao::orderBy('secretaria_id')->get());
    }

    public function salvarConfiguracao(OsConfiguracaoRequest $request): JsonResponse
    {
        $c = OsConfiguracao::updateOrCreate(
            ['secretaria_id' => $request->secretaria_id],
            ['solicitante_aponta_tecnico' => $request->boolean('solicitante_aponta_tecnico')]
        );

        return response()->json($c);
    }
}
