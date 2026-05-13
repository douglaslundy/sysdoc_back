<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineMonthlyAcquisition extends Model
{
    use HasFactory;

    protected $table = 'medicine_monthly_acquisitions';

    protected $fillable = [
        'medicine_item_id',
        'reference_month',
        'acquired_quantity',
        'unit_measure',
        'source_document',
        'note',
        'published_at',
        'updated_by_user_id',
    ];

    protected $casts = [
        'acquired_quantity' => 'decimal:2',
        'published_at' => 'datetime',
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

