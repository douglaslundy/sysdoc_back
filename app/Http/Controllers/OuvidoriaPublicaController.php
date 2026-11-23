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
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OuvidoriaPublicaController extends Controller
{
    private const MAX_FALHAS = 5;

    private const BLOQUEIO_MIN = 15;


    /** Hash gerado com o custo configurado: protocolo inexistente gasta o mesmo tempo de um existente. */
    public static function hashFalso(): string
    {
        static $hash;

        return $hash ??= Hash::make(Str::random(16));
    }

    public function __construct()
    {
        // Portal aberto = o admin liberou ao menos uma secretaria ativa (/secretarias). Roda antes da validação do FormRequest.
        // A consulta de protocolo já existente nunca é bloqueada: o cidadão não perde o acesso à resposta.
        $this->middleware(fn ($request, $next) => Secretaria::where('ativa', true)->where('ouvidoria_publica', true)->exists() ? $next($request) : abort(404))
            ->only(['opcoes', 'store']);
    }

    public function opcoes(CaptchaDesafio $captcha): JsonResponse
    {
        return response()->json([
            'secretarias' => Secretaria::where('ativa', true)->where('ouvidoria_publica', true)->orderBy('nome')->get(['id', 'nome']),
            'tipos' => collect(OuvidoriaManifestacao::TIPOS)->map(fn ($rotulo, $valor) => ['valor' => $valor, 'rotulo' => $rotulo])->values(),
            'desafio' => $captcha->gerar(),
            // Selo cifrado com a hora de abertura: o servidor mede o tempo de preenchimento sem confiar no cliente.
            'iniciado' => Crypt::encryptString((string) now()->timestamp),
        ]);
    }

    public function store(OuvidoriaPublicaRequest $request, OuvidoriaRegistroService $service): JsonResponse
    {
        $r = $service->registrar($request->dadosManifestacao(), $request->file('anexos', []));
        $m = $r['manifestacao'];
        OuvidoriaNotificador::depoisDaResposta(fn (OuvidoriaNotificador $n) => $n->registrada($m));

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

        $ok = Hash::check($senha, $m?->senha_hash ?? self::hashFalso());
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
        // Lock + leitura fresca: tentativas simultâneas não podem perder contagem e furar o limite de 5.
        DB::transaction(function () use ($m) {
            $atual = OuvidoriaManifestacao::withoutSecretaria()->lockForUpdate()->find($m->id);
            if (! $atual) {
                return;
            }
            $falhas = (int) $atual->tentativas_falhas + 1;
            if ($falhas >= self::MAX_FALHAS) {
                $atual->forceFill(['tentativas_falhas' => 0, 'bloqueado_ate' => now()->addMinutes(self::BLOQUEIO_MIN)])->save();

                return;
            }
            $atual->forceFill(['tentativas_falhas' => $falhas])->save();
        });
    }
}
