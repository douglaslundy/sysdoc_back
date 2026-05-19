<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineDailyStatus extends Model
{
    use HasFactory;

    protected $table = 'medicine_daily_statuses';

    protected $fillable = [
        'medicine_item_id',
        'reference_date',
        'availability_status',
        'available_quantity',
        'restock_forecast_date',
        'public_note',
        'published_site_at',
        'published_panel_at',
        'updated_by_user_id',
    ];

    protected $casts = [
        'reference_date' => 'date:Y-m-d',
        'restock_forecast_date' => 'date:Y-m-d',
        'available_quantity' => 'decimal:2',
        'published_site_at' => 'datetime',
        'published_panel_at' => 'datetime',
    ];

    public function medicineItem(): BelongsTo
    {
        return $this->belongsTo(MedicineItem::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }
}
