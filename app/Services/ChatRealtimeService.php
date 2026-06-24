<?php

namespace App\Services;

use App\Events\ChatRealtimeEvent;
use App\Models\ChatUsageDaily;
use Illuminate\Support\Facades\Log;

class ChatRealtimeService
{
    public function publish(int $recipientId, string $eventName, array $payload): void
    {
        try {
            broadcast(new ChatRealtimeEvent($recipientId, $eventName, $payload));
            $this->increment('events_published');
        } catch (\Throwable $e) {
            $this->increment('failed_events');
            Log::warning('Falha ao publicar evento do chat.', [
                'recipient_id' => $recipientId,
                'event' => $eventName,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function publishPresence(array $payload): void
    {
        try {
            broadcast(new ChatRealtimeEvent(null, 'presence.updated', $payload));
            $this->increment('events_published');
        } catch (\Throwable $e) {
            $this->increment('failed_events');
            Log::warning('Falha ao publicar presenca do chat.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function increment(string $column, int $amount = 1): void
    {
        $usage = ChatUsageDaily::firstOrCreate(
            ['usage_date' => now()->toDateString()],
            [
                'messages_sent' => 0,
                'events_published' => 0,
                'connection_events' => 0,
                'peak_connections' => 0,
                'attachments_sent' => 0,
                'attachment_bytes' => 0,
                'failed_events' => 0,
            ]
        );

        $usage->increment($column, $amount);
    }

    public function updatePeakConnections(int $connections): void
    {
        $usage = ChatUsageDaily::firstOrCreate(
            ['usage_date' => now()->toDateString()],
            ['peak_connections' => 0]
        );

        if ($connections > $usage->peak_connections) {
            $usage->update(['peak_connections' => $connections]);
        }
    }
}
