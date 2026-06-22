<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MedicineItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'medicine_items';

    protected $fillable = [
        'internal_code',
        'brand_name',
        'active_ingredient',
        'concentration',
        'pharmaceutical_form',
        'presentation',
        'unit_measure',
        'ean_code',
        'is_free_distribution',
        'is_controlled',
        'is_judicial_order',
        'is_high_cost',
        'active',
        'technical_notes',
    ];

    protected $casts = [
        'is_free_distribution' => 'boolean',
        'is_controlled' => 'boolean',
        'is_judicial_order' => 'boolean',
        'is_high_cost' => 'boolean',
        'active' => 'boolean',
    ];

    public function dailyStatuses(): HasMany
    {
        return $this->hasMany(MedicineDailyStatus::class);
    }

    public function monthlyAcquisitions(): HasMany
    {
        return $this->hasMany(MedicineMonthlyAcquisition::class);
    }

    public function latestDailyStatus(): HasOne
    {
        return $this->hasOne(MedicineDailyStatus::class)->latestOfMany('reference_date');
    }
}
