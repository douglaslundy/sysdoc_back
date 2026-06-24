<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatRealtimeEvent implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly ?int $recipientId,
        public readonly string $eventName,
        public readonly array $payload
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(
            $this->recipientId === null ? 'chat.presence' : 'chat.user.'.$this->recipientId
        );
    }

    public function broadcastAs(): string
    {
        return $this->eventName;
    }

    public function broadcastWith(): array
    {
        return $this->payload;
    }
}
