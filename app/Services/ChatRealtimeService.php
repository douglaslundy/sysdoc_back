<?php

namespace App\Services;

use App\Events\ChatRealtimeEvent;
use App\Models\ChatUsageDaily;
use App\Support\AfterResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChatRealtimeService
{
    private const COUNTERS = [
        'messages_sent',
        'events_published',
        'connection_events',
        'attachments_sent',
        'attachment_bytes',
        'failed_events',
    ];

    public function __construct(private readonly ChatBroadcastConfigService $broadcastConfig)
    {
    }

    /**
     * Publica o evento para um destinatario. O envio ao servico de tempo real
     * (HTTP para Pusher/Soketi) acontece DEPOIS de a resposta ser entregue ao
     * usuario, para que quem envia a mensagem nao espere a rede de cada
     * destinatario.
     */
    public function publish(int $recipientId, string $eventName, array $payload): void
    {
        $this->dispatchEvents([[$recipientId, $eventName, $payload]]);
    }

    /**
     * Varios destinatarios do mesmo evento (ex: mensagem nova) em um unico envio
     * diferido e uma unica escrita de contador.
     */
    public function publishMany(array $recipientIds, string $eventName, array $payload): void
    {
        $this->dispatchEvents(array_map(
            fn ($recipientId) => [(int) $recipientId, $eventName, $payload],
            $recipientIds
        ));
    }

    public function publishPresence(array $payload): void
    {
        $this->dispatchEvents([[null, 'presence.updated', $payload]]);
    }

    /**
     * @param  array<int, array{0: ?int, 1: string, 2: array}>  $events
     */
    private function dispatchEvents(array $events): void
    {
        if ($events === []) {
            return;
        }

        $settings = $this->broadcastConfig->apply();
        if (! $settings?->active) {
            return;
        }
        if (! $this->broadcastConfig->isReady()) {
            $this->increment('failed_events', count($events));
            Log::warning('Broadcast do chat pulado: credenciais ausentes/incompletas apesar de "ativo".', [
                'events' => count($events),
                'engine' => $settings->engine,
            ]);

            return;
        }

        $this->afterResponse(function () use ($events) {
            $published = 0;
            $failed = 0;

            foreach ($events as [$recipientId, $eventName, $payload]) {
                try {
                    broadcast(new ChatRealtimeEvent($recipientId, $eventName, $payload));
                    $published++;
                } catch (\Throwable $e) {
                    $failed++;
                    Log::warning('Falha ao publicar evento do chat.', [
                        'recipient_id' => $recipientId,
                        'event' => $eventName,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($published > 0) {
                $this->increment('events_published', $published);
            }
            if ($failed > 0) {
                $this->increment('failed_events', $failed);
            }
        });
    }

    /**
     * Executa apos a resposta HTTP ter sido enviada (terminating callbacks rodam
     * depois do fastcgi_finish_request). Em console (artisan/testes) executa na
     * hora, a menos que `chat.defer_broadcast` force o comportamento web.
     */
    public function afterResponse(\Closure $callback): void
    {
        AfterResponse::run($callback);
    }

    /**
     * Contador diario atomico em UMA instrucao (antes: firstOrCreate + increment,
     * 3 consultas por evento numa linha disputada por todos os usuarios).
     */
    public function increment(string $column, int $amount = 1): void
    {
        if (! in_array($column, self::COUNTERS, true)) {
            throw new \InvalidArgumentException("Contador de chat invalido: {$column}");
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            $now = now();
            DB::statement(
                "INSERT INTO chat_usage_daily (usage_date, {$column}, created_at, updated_at) VALUES (?, ?, ?, ?) "
                ."ON DUPLICATE KEY UPDATE {$column} = {$column} + VALUES({$column}), updated_at = VALUES(updated_at)",
                [$now->toDateString(), $amount, $now, $now]
            );

            return;
        }

        $usage = ChatUsageDaily::firstOrCreate(['usage_date' => now()->toDateString()]);
        $usage->increment($column, $amount);
    }

    public function updatePeakConnections(int $connections): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            $now = now();
            DB::statement(
                'INSERT INTO chat_usage_daily (usage_date, peak_connections, created_at, updated_at) VALUES (?, ?, ?, ?) '
                .'ON DUPLICATE KEY UPDATE peak_connections = GREATEST(peak_connections, VALUES(peak_connections))',
                [$now->toDateString(), $connections, $now, $now]
            );

            return;
        }

        $usage = ChatUsageDaily::firstOrCreate(
            ['usage_date' => now()->toDateString()],
            ['peak_connections' => 0]
        );

        if ($connections > $usage->peak_connections) {
            $usage->update(['peak_connections' => $connections]);
        }
    }
}
