<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Queue extends Model
{
    use HasFactory;

    // Definindo o nome da tabela, caso seja necessário (opcional se o nome segue o padrão plural)
    protected $table = 'queue';

    // Definindo quais atributos podem ser preenchidos em massa (mass assignment)
    protected $fillable = [
        'id_client',
        'id_specialities',
        'id_user',
        'done',
        'date_of_realized',
        'urgency',
        'obs',
        'done_by',
    ];

    protected $casts = [
        'done_at' => 'datetime',
    ];

    /**
     * Método boot para adicionar o UUID automaticamente na criação do registro.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Gera o UUID se o campo estiver vazio
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        // Data/hora da baixa: registrada quando o item passa a "realizado" e limpa se
        // for reaberto. Edicoes posteriores (obs, anexos...) nao alteram o valor.
        static::saving(function ($model) {
            if (! $model->isDirty('done')) {
                return;
            }

            $model->done_at = $model->done ? ($model->done_at ?? now()) : null;
            // Quem deu a baixa: o usuario autenticado da requisicao (ou o definido explicitamente).
            $model->done_by = $model->done
                ? ($model->done_by ?? request()->user()?->id ?? auth()->id())
                : null;
        });
    }

    /**
     * Relacionamento com a tabela Users
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    /**
     * Usuario que deu a baixa (done = 1).
     */
    public function doneBy()
    {
        return $this->belongsTo(User::class, 'done_by');
    }

    /**
     * Relacionamento com a tabela Clients
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'id_client');
    }

    /**
     * Relacionamento com a tabela Specialities
     */
    public function speciality()
    {
        return $this->belongsTo(Speciality::class, 'id_specialities');
    }

    public function attachments()
    {
        return $this->hasMany(QueueAttachment::class, 'queue_id');
    }
}
