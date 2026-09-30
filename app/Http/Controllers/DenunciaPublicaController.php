<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDenunciaRequest;
use App\Models\Fiscalizacao;
use App\Models\FiscalizacaoAttachment;
use App\Services\Fiscalizacao\FiscalizacaoProtocolo;
use App\Services\Fiscalizacao\FiscalizacaoTimeline;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Denúncia pública (sem login). A denúncia é uma fiscalização "Pendente de apuração";
 * o denunciante recebe protocolo + senha para acompanhar.
 */
class DenunciaPublicaController extends Controller
{
    private const SENHA_ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const SENHA_TAMANHO = 8;

    private const DISK = 'private';

    public function __construct(private FiscalizacaoTimeline $timeline)
    {
    }

    public function store(StoreDenunciaRequest $request): JsonResponse
    {
        // Campo isca preenchido = robô: responde como se tivesse funcionado, sem gravar nada.
        if ($request->filled('website')) {
            return response()->json($this->recibo(
                sprintf('FIS-%s-%06d', now()->format('Y'), random_int(900000, 999999)),
                $this->gerarSenha()
            ), 201);
        }

        $senha = $this->gerarSenha();

        $fiscalizacao = DB::transaction(function () use ($request, $senha) {
            $fiscalizacao = Fiscalizacao::create([
                'resultado' => 'Pendente de apuração',
                'origem' => 'denuncia',
                'assunto' => $request->input('assunto'),
                'descricao_denuncia' => $request->input('descricao_denuncia'),
                'local_endereco' => $request->input('local_endereco'),
                'estabelecimento_nome_informado' => $request->input('estabelecimento_nome_informado'),
                'denunciante_nome' => $request->input('denunciante_nome'),
                'denunciante_contato' => $request->input('denunciante_contato'),
            ]);

            // Protocolo e hash da senha são gravados sem eventos: a senha não pode ir para a auditoria.
            $fiscalizacao->forceFill([
                'protocolo' => FiscalizacaoProtocolo::for($fiscalizacao->id, $fiscalizacao->created_at),
                'senha_consulta_hash' => Hash::make($senha),
            ])->saveQuietly();

            $anexos = 0;
            foreach ((array) $request->file('files', []) as $file) {
                FiscalizacaoAttachment::create([
                    'fiscalizacao_id' => $fiscalizacao->id,
                    'uploaded_by' => null,
                    'origem' => 'denunciante',
                    'disk' => self::DISK,
                    'path' => $file->store('fiscalizacao-attachments/'.$fiscalizacao->id, self::DISK),
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType() ?? $file->getClientMimeType(),
                    'size_bytes' => (int) $file->getSize(),
                ]);
                $anexos++;
            }

            $this->timeline->registrar(
                $fiscalizacao,
                'denuncia_recebida',
                'Denúncia recebida',
                true,
                null,
                ['anexos' => $anexos]
            );

            return $fiscalizacao;
        });

        return response()->json($this->recibo($fiscalizacao->protocolo, $senha), 201);
    }

    private function recibo(string $protocolo, string $senha): array
    {
        return [
            'protocolo' => $protocolo,
            'senha' => $senha,
            'url_consulta' => rtrim((string) config('app.frontend_url'), '/').'/denuncia/consulta?protocolo='.$protocolo,
        ];
    }

    private function gerarSenha(): string
    {
        $senha = '';
        $max = strlen(self::SENHA_ALFABETO) - 1;
        for ($i = 0; $i < self::SENHA_TAMANHO; $i++) {
            $senha .= self::SENHA_ALFABETO[random_int(0, $max)];
        }

        return $senha;
    }
}
