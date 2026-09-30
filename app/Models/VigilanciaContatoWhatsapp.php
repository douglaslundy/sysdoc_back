<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Profissional da Vigilância Sanitária que recebe avisos por WhatsApp. */
class VigilanciaContatoWhatsapp extends Model
{
    protected $table = 'vigilancia_contatos_whatsapp';

    protected $fillable = ['nome', 'telefone', 'ativo'];

    protected $casts = ['ativo' => 'boolean'];
}
