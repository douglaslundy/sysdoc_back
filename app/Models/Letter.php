<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Letter extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_user',
        'number',
        'subject_matter',
        'sender',
        'recipient',
        'obs',
        'fileurl',
        'summary',
        'dispatched',
    ];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'id_user');
    }

    public function attachments()
    {
        return $this->hasMany(LetterAttachment::class, 'letter_id');
    }
}
