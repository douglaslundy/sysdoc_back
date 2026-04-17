<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ordinance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'number',
        'year',
        'type',
        'title',
        'subject',
        'summary',
        'content',
        'legal_basis',
        'department',
        'signatory_name',
        'signatory_role',
        'publication_date',
        'file_path',
        'notes',
    ];

    protected $casts = [
        'number' => 'integer',
        'year' => 'integer',
        'publication_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}