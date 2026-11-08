<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyMedicinePanelSetting extends Model
{
    use \App\Models\Concerns\BelongsToSecretaria;

    use HasFactory;

    protected $table = 'pharmacy_medicine_panel_settings';

    protected $fillable = [
        'secretaria_id',
        'filter_is_free_distribution',
        'filter_is_controlled',
        'filter_is_judicial_order',
        'filter_is_high_cost',
        'filter_active',
        'filter_show_all',
    ];

    protected $casts = [
        'filter_is_free_distribution' => 'boolean',
        'filter_is_controlled' => 'boolean',
        'filter_is_judicial_order' => 'boolean',
        'filter_is_high_cost' => 'boolean',
        'filter_active' => 'boolean',
        'filter_show_all' => 'boolean',
    ];

    /** Painel da secretaria ativa (ou da informada: admin em "Todas", rota pública da Saúde). */
    public static function current(?int $secretariaId = null): self
    {
        return self::withoutSecretaria()->firstOrCreate(['secretaria_id' => $secretariaId ?? \App\Support\EscopoAlmoxarifado::id()], [
            'filter_is_free_distribution' => false,
            'filter_is_controlled' => false,
            'filter_is_judicial_order' => false,
            'filter_is_high_cost' => false,
            'filter_active' => true,
            'filter_show_all' => false,
        ]);
    }
}
