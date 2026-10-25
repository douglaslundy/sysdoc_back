<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIluminacaoPosteFotoRequest;
use App\Http\Resources\IluminacaoPosteResource;
use App\Models\IluminacaoPoste;
use App\Models\IluminacaoPosteFoto;
use App\Services\Iluminacao\IluminacaoFotoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IluminacaoPosteFotoController extends Controller
{
    public function __construct(private IluminacaoFotoStorage $fotos)
    {
    }

    public function store(StoreIluminacaoPosteFotoRequest $request, IluminacaoPoste $poste)
    {
        $user = $request->user();

        if ($user->profile !== 'admin' && $poste->cadastrado_por !== $user->id) {
            return response()->json(['message' => 'Você só pode alterar os postes que cadastrou.'], 403);
        }

        if (! $poste->editavelPor($user)) {
            return response()->json(['message' => 'Este poste não pode mais receber fotos de cadastro.'], 422);
        }

        $erro = DB::transaction(function () use ($request, $poste, $user) {
            // Trava o poste para duas requisições simultâneas não furarem a cota.
            IluminacaoPoste::whereKey($poste->id)->lockForUpdate()->first();

            if (count($request->file('fotos')) > $this->fotos->vagasNoCadastro($poste)) {
                return 'O poste aceita no máximo '.IluminacaoFotoStorage::MAX_FOTOS.' fotos de cadastro.';
            }

            $this->fotos->guardar($poste, $request->file('fotos'), 'cadastro', $user->id);

            return null;
        });

        if ($erro) {
            return response()->json(['message' => $erro], 422);
        }

        return (new IluminacaoPosteResource($poste->load(['cadastrador:id,name', 'fotos'])->loadCount('fotos')))
            ->response()->setStatusCode(201);
    }

    public function download(IluminacaoPoste $poste, IluminacaoPosteFoto $foto)
    {
        abort_unless($foto->poste_id === $poste->id, 404);
        abort_unless(Storage::disk($foto->disk)->exists($foto->path), 404);

        // Content-Type vem do banco (só imagens validadas no upload) e nosniff impede o navegador de reinterpretar.
        return Storage::disk($foto->disk)->response($foto->path, null, [
            'Content-Type' => $foto->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }

    public function destroy(Request $request, IluminacaoPoste $poste, IluminacaoPosteFoto $foto)
    {
        abort_unless($foto->poste_id === $poste->id, 404);

        $user = $request->user();
        $permitido = $user->profile === 'admin'
            || ($foto->momento === 'cadastro' && $poste->editavelPor($user));

        if (! $permitido) {
            return response()->json(['message' => 'Você não pode remover esta foto.'], 403);
        }

        $this->fotos->remover($foto);

        return response()->json(['message' => 'Foto removida.']);
    }
}
