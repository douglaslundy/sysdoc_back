<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdinanceAttachment extends Model
{
    use HasFactory;

    // Herda a secretaria do registro pai (sem coluna própria).
    protected static function booted(): void
    {
        static::addGlobalScope(new \App\Models\Scopes\SecretariaDoPaiScope('ordinance_id', 'ordinances'));
    }

    protected $fillable = [
        'ordinance_id',
        'uploaded_by',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    public function ordinance()
    {
        return $this->belongsTo(Ordinance::class, 'ordinance_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
