<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentConfig extends Model
{
    protected $table = 'document_configs';

    protected $fillable = [
        'triple_signature_enabled',
        'triple_signature_sigilos',
        'signer_user_1_id',
        'signer_user_2_id',
        'signer_user_3_id',
    ];

    protected $casts = [
        'triple_signature_enabled' => 'boolean',
        'triple_signature_sigilos' => 'array',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'triple_signature_enabled' => false,
            'triple_signature_sigilos' => ['interno', 'restrito'],
        ]);
    }

    public function signerUserIds(): array
    {
        return array_values(array_unique(array_filter([
            $this->signer_user_1_id,
            $this->signer_user_2_id,
            $this->signer_user_3_id,
        ])));
    }

    public function requiresTripleSignatureFor(string $sigilo): bool
    {
        if (! $this->triple_signature_enabled) {
            return false;
        }

        $sigilos = array_values(array_filter(array_map('strval', (array) $this->triple_signature_sigilos)));

        return in_array($sigilo, $sigilos, true);
    }
}
