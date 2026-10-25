<?php

namespace App\Services\Iluminacao;

use App\Models\IluminacaoPoste;
use App\Models\IluminacaoPosteFoto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class IluminacaoFotoStorage
{
    public const DISK = 'private';

    public const MAX_FOTOS = 4;

    /**
     * Grava os arquivos no disco privado (nome gerado pelo servidor) e cria as linhas.
     * Se um falhar, apaga o que já tinha sido gravado e relança.
     *
     * @param  UploadedFile[]  $arquivos
     * @return IluminacaoPosteFoto[]
     */
    public function guardar(IluminacaoPoste $poste, array $arquivos, string $momento, ?int $userId, ?int $movimentacaoId = null): array
    {
        $criadas = [];

        try {
            foreach ($arquivos as $arquivo) {
                $path = $arquivo->store("iluminacao/{$poste->id}", self::DISK);

                $criadas[] = IluminacaoPosteFoto::create([
                    'poste_id' => $poste->id,
                    'movimentacao_id' => $movimentacaoId,
                    'momento' => $momento,
                    'uploaded_by' => $userId,
                    'disk' => self::DISK,
                    'path' => $path,
                    'original_name' => $arquivo->getClientOriginalName(),
                    'mime_type' => $arquivo->getMimeType() ?? $arquivo->getClientMimeType(),
                    'size_bytes' => (int) $arquivo->getSize(),
                ]);
            }
        } catch (Throwable $e) {
            foreach ($criadas as $foto) {
                Storage::disk($foto->disk)->delete($foto->path);
                $foto->delete();
            }

            throw $e;
        }

        return $criadas;
    }

    public function remover(IluminacaoPosteFoto $foto): void
    {
        Storage::disk($foto->disk)->delete($foto->path);
        $foto->delete();
    }

    /** Vagas livres no cadastro do poste (máx. 4 fotos de cadastro). */
    public function vagasNoCadastro(IluminacaoPoste $poste): int
    {
        return max(0, self::MAX_FOTOS - $poste->fotos()->where('momento', 'cadastro')->count());
    }
}
