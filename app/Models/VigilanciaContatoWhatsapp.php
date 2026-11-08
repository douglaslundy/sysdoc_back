<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Profissional da Vigilância Sanitária que recebe avisos por WhatsApp. */
class VigilanciaContatoWhatsapp extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    protected $table = 'vigilancia_contatos_whatsapp';

    protected $fillable = ['secretaria_id', 'nome', 'telefone', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
