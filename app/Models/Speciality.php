<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Speciality extends Model
{
    use HasFactory;

    // Define a tabela associada à model (opcional, se o nome da tabela for o plural do nome da model)
    protected $table = 'specialities';

    // Define os campos que podem ser preenchidos via mass assignment
    protected $fillable = [
        'id_user',
        'name',
    ];

    /**
     * Define o relacionamento Many-to-One com a tabela users.
     * Um usuário pode ter várias especialidades, mas uma especialidade pertence a um único usuário.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
