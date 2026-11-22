<?php

namespace App\Services\Ouvidoria;

use App\Models\OuvidoriaAnexo;
use App\Models\OuvidoriaHistorico;
use App\Models\OuvidoriaManifestacao;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Throwable;

class OuvidoriaRegistroService
{
    public const DISK = 'private';

    public const PRAZO_DIAS = 30;

    // Sem 0/O/1/I para a senha poder ser ditada ao telefone.
    private const ALFABETO = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * @param  UploadedFile[]  $arquivos
     * @return array{manifestacao: OuvidoriaManifestacao, senha: string}
     */
    public function registrar(array $dados, array $arquivos = []): array
    {
        $senha = $this->aleatorio(8);
        $gravados = [];

        try {
            $m = DB::transaction(function () use ($dados, $arquivos, $senha, &$gravados) {
                $m = OuvidoriaManifestacao::create($dados + [
                    'protocolo' => $this->protocoloUnico(),
                    'senha_hash' => Hash::make($senha),
                    'status' => 'recebida',
                    'prazo_em' => now()->addDays(self::PRAZO_DIAS),
                ]);

                foreach ($arquivos as $arquivo) {
                    $path = $arquivo->storeAs('ouvidoria/'.$m->id, bin2hex(random_bytes(16)).'.'.$arquivo->extension(), self::DISK);
                    $gravados[] = $path;
                    OuvidoriaAnexo::create([
                        'manifestacao_id' => $m->id, 'disk' => self::DISK, 'path' => $path,
                        'original_name' => mb_substr($arquivo->getClientOriginalName(), 0, 250),
                        'mime_type' => $arquivo->getMimeType(), 'size_bytes' => $arquivo->getSize(),
                    ]);
                }

                OuvidoriaHistorico::create([
                    'manifestacao_id' => $m->id, 'user_id' => null, 'acao' => 'registrada',
                    'status_para' => 'recebida', 'created_at' => now(),
                ]);

                return $m;
            });
        } catch (Throwable $e) {
            foreach ($gravados as $path) {
                Storage::disk(self::DISK)->delete($path);
            }
            throw $e;
        }

        return ['manifestacao' => $m, 'senha' => $senha];
    }

    private function protocoloUnico(): string
    {
        do {
            $protocolo = 'OUV-'.now()->year.'-'.$this->aleatorio(8);
        } while (OuvidoriaManifestacao::withoutSecretaria()->where('protocolo', $protocolo)->exists());

        return $protocolo;
    }

    private function aleatorio(int $n): string
    {
        $s = '';
        for ($i = 0; $i < $n; $i++) {
            $s .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return $s;
    }
}
