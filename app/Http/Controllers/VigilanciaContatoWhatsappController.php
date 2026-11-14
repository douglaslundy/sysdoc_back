<?php

namespace App\Http\Controllers;

use App\Http\Requests\VigilanciaContatoWhatsappRequest;
use App\Models\VigilanciaContatoWhatsapp;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Http\JsonResponse;

class VigilanciaContatoWhatsappController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(VigilanciaContatoWhatsapp::query()->orderBy('nome')->get());
    }

    public function store(VigilanciaContatoWhatsappRequest $request): JsonResponse
    {
        $contato = VigilanciaContatoWhatsapp::create([
            'secretaria_id' => EscopoAlmoxarifado::id($request->input('secretaria_id')), // admin em "Todas" sem escolher = 422
            'nome' => $request->validated('nome'),
            'telefone' => $request->validated('telefone'),
            'ativo' => $request->boolean('ativo', true),
        ]);

        return response()->json($contato, 201);
    }

    public function update(VigilanciaContatoWhatsappRequest $request, VigilanciaContatoWhatsapp $contato): JsonResponse
    {
        $contato->update($request->validated());

        return response()->json($contato->fresh());
    }

    public function destroy(VigilanciaContatoWhatsapp $contato): JsonResponse
    {
        $contato->delete();

        return response()->json(['message' => 'Contato removido.']);
    }
}
