<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNotice extends Model
{
    use HasFactory;

    protected $table = 'system_notices';

    protected $fillable = [
        'title',
        'subtitle',
        'body',
        'image_data',
        'times_per_day',
        'interval_minutes',
        'target_user_id',
        'valid_until',
        'is_active',
        'created_by_user_id',
    ];

    protected $casts = [
        'valid_until' => 'date:Y-m-d',
        'is_active' => 'boolean',
    ];

    public function views(): HasMany
    {
        return $this->hasMany(SystemNoticeView::class, 'system_notice_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
