<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemNoticeView extends Model
{
    use HasFactory;

    protected $table = 'system_notice_views';

    protected $fillable = [
        'system_notice_id',
        'user_id',
        'shown_at',
    ];

    protected $casts = [
        'shown_at' => 'datetime',
    ];

    public function notice(): BelongsTo
    {
        return $this->belongsTo(SystemNotice::class, 'system_notice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
