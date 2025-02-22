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
        'obs'
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
    }

    /**
     * Relacionamento com a tabela Users
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
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
}
