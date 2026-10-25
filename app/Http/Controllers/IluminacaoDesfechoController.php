<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReabrirIluminacaoPosteRequest;
use App\Http\Requests\StoreIluminacaoDesfechoRequest;
use App\Http\Resources\IluminacaoPosteResource;
use App\Models\IluminacaoPoste;
use App\Services\Iluminacao\IluminacaoDesfechoService;
use DomainException;

class IluminacaoDesfechoController extends Controller
{
    public function __construct(private IluminacaoDesfechoService $desfechos)
    {
    }

    public function store(StoreIluminacaoDesfechoRequest $request, IluminacaoPoste $poste)
    {
        try {
            $poste = $this->desfechos->registrar(
                $poste,
                $request->user(),
                $request->input('resultado'),
                $request->input('motivo'),
                $request->file('fotos', [])
            );
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new IluminacaoPosteResource($this->completo($poste));
    }

    public function reabrir(ReabrirIluminacaoPosteRequest $request, IluminacaoPoste $poste)
    {
        try {
            $poste = $this->desfechos->reabrir($poste, $request->user(), $request->input('motivo'));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new IluminacaoPosteResource($this->completo($poste));
    }

    private function completo(IluminacaoPoste $poste): IluminacaoPoste
    {
        return $poste->load(['cadastrador:id,name', 'fotos'])->loadCount('fotos');
    }
}
