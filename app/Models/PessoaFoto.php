<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PessoaFoto extends Model
{
    protected $table = 'pessoa_fotos';

    protected $fillable = ['chave', 'disk', 'path', 'mime_type', 'size_bytes', 'updated_by'];

    /** Chave da foto: o CPF (só dígitos) une cidadão e usuário; sem CPF vale o próprio registro. */
    public static function chaveDe(Client|User $pessoa): string
    {
        $cpf = preg_replace('/\D/', '', (string) $pessoa->cpf);

        if (strlen($cpf) === 11) {
            return "cpf:{$cpf}";
        }

        return ($pessoa instanceof Client ? 'client:' : 'user:').$pessoa->getKey();
    }

    /**
     * Foto da pessoa. Se ela ganhou CPF depois de ter foto, a foto sai da chave do registro e passa para a do CPF;
     * se o CPF já tinha foto (de outro cadastro), a do CPF vale e a antiga é apagada.
     */
    public static function daPessoa(Client|User $pessoa): ?self
    {
        $chave = self::chaveDe($pessoa);
        $foto = self::where('chave', $chave)->first();

        if (! str_starts_with($chave, 'cpf:')) {
            return $foto;
        }

        $antiga = self::where('chave', ($pessoa instanceof Client ? 'client:' : 'user:').$pessoa->getKey())->first();

        if (! $antiga) {
            return $foto;
        }

        if ($foto) {
            Storage::disk($antiga->disk)->delete($antiga->path);
            $antiga->delete();

            return $foto;
        }

        $antiga->update(['chave' => $chave]);

        return $antiga;
    }
}
