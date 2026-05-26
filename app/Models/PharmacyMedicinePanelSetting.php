<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyMedicinePanelSetting extends Model
{
    use HasFactory;

    protected $table = 'pharmacy_medicine_panel_settings';

    protected $fillable = [
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

    public static function current(): self
    {
        return self::query()->firstOrCreate([], [
            'filter_is_free_distribution' => false,
            'filter_is_controlled' => false,
            'filter_is_judicial_order' => false,
            'filter_is_high_cost' => false,
            'filter_active' => true,
            'filter_show_all' => false,
        ]);
    }
}
