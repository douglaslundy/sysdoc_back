<?php

namespace App\Services\Laboratorio;

use App\Mail\ResultadoLiberadoMail;
use App\Models\ResultadoCampo;
use App\Models\ResultadoExame;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class ResultadoExameService
{
    public function __construct(private LaudoPdfService $laudoPdfService)
    {
    }

    public function salvarCampos(ResultadoExame $resultado, array $campos): void
    {
        DB::beginTransaction();
        try {
            ResultadoCampo::where('resultado_exame_id', $resultado->id)->delete();

            $resultado->load(['pedido.cliente']);
            $perfil = $this->detectarPerfil($resultado->pedido->cliente);

            foreach ($campos as $campo) {
                $statusReferencia = 'indefinido';

                if (isset($campo['exame_campo_id'])) {
                    $exameCampo = \App\Models\ExameCampo::with('referencias')->find($campo['exame_campo_id']);
                    if ($exameCampo && isset($campo['valor_numerico']) && $campo['valor_numerico'] !== null) {
                        $ref = $exameCampo->referenciaParaPerfil($perfil);
                        if ($ref) {
                            $v = (float) $campo['valor_numerico'];
                            if ($ref->valor_min !== null && $ref->valor_max !== null) {
                                if ($v < $ref->valor_min) {
                                    $statusReferencia = 'baixo';
                                } elseif ($v > $ref->valor_max) {
                                    $statusReferencia = 'alto';
                                } else {
                                    $statusReferencia = 'normal';
                                }
                            }
                        }
                    }
                }

                ResultadoCampo::create([
                    'resultado_exame_id' => $resultado->id,
                    'exame_campo_id' => $campo['exame_campo_id'],
                    'exame_id' => $campo['exame_id'],
                    'valor_numerico' => $campo['valor_numerico'] ?? null,
                    'valor_texto' => $campo['valor_texto'] ?? null,
                    'status_referencia' => $statusReferencia,
                    'observacao' => $campo['observacao'] ?? null,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function liberar(ResultadoExame $resultado, int $userId): array
    {
        if ($resultado->data_liberacao) {
            throw new Exception('Este resultado já foi liberado.');
        }

        $totalCampos = $resultado->load('pedido.exames.camposAtivos')->pedido->exames
            ->flatMap(fn ($e) => $e->camposAtivos)
            ->count();

        $camposPreenchidos = $resultado->campos()
            ->where(function ($q) {
                $q->whereNotNull('valor_numerico')
                    ->orWhere(fn ($q2) => $q2->whereNotNull('valor_texto')->where('valor_texto', '!=', ''));
            })->count();

        if ($totalCampos > 0 && $camposPreenchidos === 0) {
            throw new Exception('Preencha ao menos um campo antes de liberar o resultado.');
        }

        DB::beginTransaction();
        try {
            if (! $resultado->protocolo) {
                $protocolo = ResultadoExame::gerarProtocolo();
                $senha = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $resultado->protocolo = $protocolo;
                $resultado->senha_hash = Hash::make($senha);
            } else {
                $senha = null;
            }

            $resultado->liberado_por = $userId;
            $resultado->data_liberacao = now();
            $resultado->data_validade = now()->addYear();
            $resultado->save();

            $pdfPath = $this->laudoPdfService->gerar($resultado);
            $resultado->pdf_path = $pdfPath;
            $resultado->save();

            $resultado->pedido()->update(['status' => 'liberado']);

            $resultado->load(['pedido.cliente', 'campos.campo']);
            $clienteEmail = $resultado->pedido->cliente?->email;
            $config = \App\Models\LabConfig::get();
            if ($config->email_habilitado && $clienteEmail) {
                Mail::to($clienteEmail)
                    ->queue(new ResultadoLiberadoMail($resultado, $senha ?? ''));
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return ['resultado' => $resultado->fresh(), 'senha' => $senha];
    }

    public function consultarPublico(string $protocolo, string $senha): ?ResultadoExame
    {
        $resultado = ResultadoExame::where('protocolo', strtoupper($protocolo))
            ->with(['pedido.cliente', 'campos.campo.exame'])
            ->first();

        if (! $resultado || ! $resultado->estaValido()) {
            return null;
        }

        if (! $resultado->verificarSenha($senha)) {
            return null;
        }

        return $resultado;
    }

    private function detectarPerfil($cliente): string
    {
        if (! $cliente) {
            return 'geral';
        }

        $sexo = $cliente->sexo ?? null;
        $idade = null;

        if (! empty($cliente->born_date)) {
            $idade = now()->diffInYears($cliente->born_date);
        }

        if ($idade !== null) {
            if ($idade < 12) {
                return 'crianca';
            }
            if ($idade >= 60) {
                return 'idoso';
            }
        }

        if ($sexo === 'MASCULINE') {
            return 'adulto_m';
        }
        if ($sexo === 'FEMININE') {
            return 'adulto_f';
        }

        return 'geral';
    }
}
