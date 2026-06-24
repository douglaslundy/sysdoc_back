<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Document extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'document_type_id',
        'titulo',
        'resumo',
        'sigilo',
        'status',
        'current_version_number',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'current_version_number' => 'integer',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class, 'document_id')->latestOfMany('version_number');
    }
}
