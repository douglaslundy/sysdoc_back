<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatUsageDaily extends Model
{
    protected $table = 'chat_usage_daily';

    protected $fillable = [
        'usage_date', 'messages_sent', 'events_published', 'connection_events',
        'peak_connections', 'attachments_sent', 'attachment_bytes', 'failed_events',
    ];

    protected $casts = ['usage_date' => 'date'];
}
