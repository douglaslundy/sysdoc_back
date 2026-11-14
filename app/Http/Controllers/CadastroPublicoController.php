<?php

namespace App\Http\Controllers;

use App\Http\Requests\CadastroPublicoRequest;
use App\Models\Secretaria;
use App\Services\Cadastro\CadastroSolicitacaoService;
use App\Services\Cadastro\CaptchaDesafio;

class CadastroPublicoController extends Controller
{
    public function __construct()
    {
        // Roda antes de qualquer trabalho (inclusive a validação do FormRequest).
        $this->middleware(fn ($request, $next) => config('cadastro.publico_habilitado') ? $next($request) : abort(404));
    }

    public function secretarias()
    {
        return Secretaria::where('ativa', true)->orderBy('nome')->get(['id', 'nome']);
    }

    public function desafio(CaptchaDesafio $captcha)
    {
        return $captcha->gerar();
    }

    public function store(CadastroPublicoRequest $request, CadastroSolicitacaoService $service)
    {
        $service->registrar($request->dadosCadastro());

        return response()->json(['mensagem' => 'Pedido recebido. Você será avisado quando for analisado.'], 202);
    }
}
