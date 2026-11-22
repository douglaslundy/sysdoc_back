<?php

namespace App\Http\Controllers;

use App\Http\Requests\OuvidoriaConsultaRequest;
use App\Http\Requests\OuvidoriaPublicaRequest;
use App\Models\OuvidoriaManifestacao;
use App\Models\Secretaria;
use App\Services\Cadastro\CaptchaDesafio;
use App\Services\Ouvidoria\OuvidoriaNotificador;
use App\Services\Ouvidoria\OuvidoriaRegistroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class OuvidoriaPublicaController extends Controller
{
    private const MAX_FALHAS = 5;

    private const BLOQUEIO_MIN = 15;

    // Hash fixo: protocolo inexistente gasta o mesmo tempo de um existente (sem enumeração por tempo).
    private const HASH_FALSO = '$2y$12$usesomesillystringforeOZ9tqTzC0X4mB9VQe0m5Jj5x6o1p3nXe';

    public function __construct()
    {
        // Roda antes de qualquer trabalho (inclusive a validação do FormRequest).
        $this->middleware(fn ($request, $next) => config('ouvidoria.publica_habilitada') ? $next($request) : abort(404));
    }

    public function opcoes(CaptchaDesafio $captcha): JsonResponse
    {
        return response()->json([
            'secretarias' => Secretaria::where('ativa', true)->where('ouvidoria_publica', true)->orderBy('nome')->get(['id', 'nome']),
            'tipos' => collect(OuvidoriaManifestacao::TIPOS)->map(fn ($rotulo, $valor) => ['valor' => $valor, 'rotulo' => $rotulo])->values(),
            'desafio' => $captcha->gerar(),
        ]);
    }

    public function store(OuvidoriaPublicaRequest $request, OuvidoriaRegistroService $service): JsonResponse
    {
        $r = $service->registrar($request->dadosManifestacao(), $request->file('anexos', []));
        $m = $r['manifestacao'];
        app(OuvidoriaNotificador::class)->registrada($m);

        return response()->json([
            'protocolo' => $m->protocolo,
            'senha' => $r['senha'],
            'url_consulta' => rtrim((string) config('app.frontend_url'), '/').'/ouvidoria/consulta?protocolo='.$m->protocolo,
            'prazo_em' => $m->prazo_em,
        ], 201);
    }

    /** Resposta idêntica para protocolo inexistente, senha errada e protocolo bloqueado. */
    public function consultar(OuvidoriaConsultaRequest $request): JsonResponse
    {
        $m = OuvidoriaManifestacao::withoutSecretaria()->where('protocolo', $request->input('protocolo'))->first();
        $senha = (string) $request->input('senha');

        $ok = Hash::check($senha, $m?->senha_hash ?? self::HASH_FALSO);
        $bloqueada = $m && $m->bloqueado_ate && $m->bloqueado_ate->isFuture();

        if (! $m || $bloqueada || ! $ok) {
            if ($m && ! $bloqueada) {
                $this->registrarFalha($m);
            }

            return response()->json(['message' => 'Protocolo ou senha inválidos.'], 422);
        }

        if ($m->tentativas_falhas > 0) {
            $m->forceFill(['tentativas_falhas' => 0])->save();
        }

        return response()->json([
            'protocolo' => $m->protocolo,
            'tipo' => $m->tipo,
            'tipo_rotulo' => OuvidoriaManifestacao::TIPOS[$m->tipo] ?? $m->tipo,
            'assunto' => $m->assunto,
            'status' => $m->status,
            'created_at' => $m->created_at,
            'resposta' => $m->resposta,
            'respondida_em' => $m->respondida_em,
        ]);
    }

    private function registrarFalha(OuvidoriaManifestacao $m): void
    {
        $falhas = (int) $m->tentativas_falhas + 1;
        if ($falhas >= self::MAX_FALHAS) {
            $m->forceFill(['tentativas_falhas' => 0, 'bloqueado_ate' => now()->addMinutes(self::BLOQUEIO_MIN)])->save();

            return;
        }
        $m->forceFill(['tentativas_falhas' => $falhas])->save();
    }
}
