<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResultadoExame extends Model
{
    use HasFactory;

    protected $fillable = [
        'pedido_exame_id', 'liberado_por', 'protocolo', 'senha_hash',
        'pdf_path', 'data_liberacao', 'data_validade', 'ativo',
    ];

    protected $hidden = ['senha_hash'];

    public function pedido()
    {
        return $this->hasOne(PedidoExame::class, 'id', 'pedido_exame_id');
    }

    public function campos()
    {
        return $this->hasMany(ResultadoCampo::class, 'resultado_exame_id', 'id');
    }

    public function liberadoPor()
    {
        return $this->hasOne(User::class, 'id', 'liberado_por');
    }

    public function verificarSenha(string $senha): bool
    {
        return Hash::check($senha, $this->senha_hash);
    }

    public function estaValido(): bool
    {
        if (! $this->ativo || ! $this->data_liberacao) {
            return false;
        }
        if ($this->data_validade && now()->gt($this->data_validade)) {
            return false;
        }

        return true;
    }

    public static function gerarProtocolo(): string
    {
        do {
            $protocolo = 'LAB-'.strtoupper(Str::random(8));
        } while (self::where('protocolo', $protocolo)->exists());

        return $protocolo;
    }
}
