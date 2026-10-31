<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
