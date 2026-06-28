<?php

namespace App\Http\Controllers;

use App\Models\ChatAttachment;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatRealtimeConfig;
use App\Models\ChatUsageDaily;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\AuditService;
use App\Services\ChatRealtimeService;
use App\Services\ChatBroadcastConfigService;
use App\Services\SystemAlertService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ChatController extends Controller
{
    private const DISK = 'private';

    public function __construct(
        private readonly ChatRealtimeService $realtime,
        private readonly ChatBroadcastConfigService $broadcastConfig
    ) {
    }

    public function users(Request $request): JsonResponse
    {
        $currentUserId = (int) $request->user()->id;
        $this->removeStaleConnections();
        $presences = UserPresence::query()->get()->keyBy('user_id');

        $users = User::query()
            ->where('active', true)
            ->whereKeyNot($currentUserId)
            ->orderBy('name')
            ->get(['id', 'name', 'preferred_name', 'email', 'profile'])
            ->filter(fn (User $user) => $user->canUseChat())
            ->map(function (User $user) use ($presences) {
                $presence = $presences->get($user->id);
                $recent = $presence?->last_seen_at?->greaterThanOrEqualTo(now()->subMinutes(2)) ?? false;
                $online = $recent && ($presence?->connection_count ?? 0) > 0;

                return [
                    ...$user->toArray(),
                    'name' => $user->chatDisplayName(),
                    'presence' => $online ? ($presence->status ?: 'online') : 'offline',
                    'is_online' => $online,
                    'last_seen_at' => $presence?->last_seen_at?->toISOString(),
                ];
            })
            ->values();

        return response()->json($users);
    }

    public function conversations(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $items = ChatConversation::query()
            ->whereHas('participants', fn (Builder $query) => $query
                ->where('users.id', $userId)
                ->whereNull('chat_conversation_participants.deleted_at'))
            ->with([
                'participants:id,name,preferred_name,email',
                'messages' => fn ($query) => $query->with(['attachments', 'sender:id,name,preferred_name,email'])->latest()->limit(1),
            ])
            ->orderByDesc('last_message_at')
            ->get()
            ->map(fn (ChatConversation $conversation) => $this->conversationPayload($conversation, $userId));

        return response()->json($items);
    }

    public function startConversation(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('active', true)],
        ]);

        $userId = (int) $request->user()->id;
        $otherId = (int) $data['user_id'];

        if ($userId === $otherId) {
            return response()->json(['message' => 'Selecione outro usuário para iniciar a conversa.'], 422);
        }

        $otherUser = User::find($otherId);
        if (! $otherUser?->canUseChat()) {
            return response()->json(['message' => 'O usuário selecionado não possui acesso ao chat.'], 422);
        }

        $conversation = ChatConversation::query()
            ->where('type', 'direct')
            ->whereHas('participants', fn ($query) => $query->where('users.id', $userId))
            ->whereHas('participants', fn ($query) => $query->where('users.id', $otherId))
            ->withCount('participants')
            ->get()
            ->firstWhere('participants_count', 2);

        if (! $conversation) {
            $conversation = DB::transaction(function () use ($userId, $otherId) {
                $conversation = ChatConversation::create([
                    'type' => 'direct',
                    'created_by' => $userId,
                ]);
                $conversation->participants()->attach([
                    $userId => ['joined_at' => now()],
                    $otherId => ['joined_at' => now()],
                ]);
                return $conversation;
            });
        } else {
            DB::table('chat_conversation_participants')
                ->where('conversation_id', $conversation->id)
                ->whereIn('user_id', [$userId, $otherId])
                ->update(['deleted_at' => null, 'updated_at' => now()]);
        }

        $conversation->load('participants:id,name,preferred_name,email');

        app(SystemAlertService::class)->dispatch('chat', 'chat_conversa_iniciada', [
            'conversation' => $conversation,
            'sender' => $request->user(),
            'recipient' => $otherUser,
            'participants' => $conversation->participants->all(),
            'requester' => $request->user(),
        ]);

        return response()->json($this->conversationPayload($conversation, $userId), 201);
    }

    public function messages(Request $request, ChatConversation $conversation): JsonResponse
    {
        $this->authorizeParticipant($conversation, (int) $request->user()->id);

        $perPage = max(10, min(100, (int) $request->input('per_page', 30)));
        $query = $conversation->messages()
            ->with(['sender:id,name,preferred_name,email', 'attachments'])
            ->latest('id');

        if ($request->filled('search')) {
            $query->where('body', 'like', '%'.trim((string) $request->input('search')).'%');
        }

        $page = $query->paginate($perPage);
        $page->getCollection()->transform(fn (ChatMessage $message) => $this->messagePayload($message));

        return response()->json($page);
    }

    public function sendMessage(Request $request, ChatConversation $conversation): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->authorizeParticipant($conversation, $userId);

        $maxKb = $this->effectiveMaxAttachmentKb();
        $data = $request->validate([
            'body' => ['nullable', 'string', 'max:'.config('chat.max_message_length')],
            'file' => [
                'nullable',
                'file',
                'max:'.$maxKb,
                'mimes:'.implode(',', config('chat.allowed_extensions')),
                'mimetypes:'.implode(',', config('chat.allowed_mimes')),
            ],
        ], [
            'file.uploaded' => 'O arquivo excede o limite permitido pelo servidor ('.$this->formatAttachmentLimitLabel($maxKb).').',
            'file.max' => 'O arquivo excede o limite permitido de '.$this->formatAttachmentLimitLabel($maxKb).'.',
            'file.mimes' => 'Envie apenas arquivos JPG, JPEG, PNG, WEBP, TXT ou PDF.',
            'file.mimetypes' => 'Envie apenas arquivos JPG, JPEG, PNG, WEBP, TXT ou PDF.',
        ]);

        $body = trim(strip_tags((string) ($data['body'] ?? '')));
        if ($body === '' && ! $request->hasFile('file')) {
            return response()->json(['message' => 'Digite uma mensagem ou selecione um arquivo.'], 422);
        }

        $message = DB::transaction(function () use ($request, $conversation, $userId, $body) {
            $file = $request->file('file');
            $messageType = $this->messageType($file?->getClientOriginalExtension(), $body !== '');

            $message = ChatMessage::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $userId,
                'body' => $body !== '' ? $body : null,
                'message_type' => $messageType,
                'status' => 'sent',
            ]);

            if ($file) {
                $storedName = Str::uuid().'.'.strtolower($file->getClientOriginalExtension());
                $directory = 'chat-attachments/'.$conversation->id;
                $path = $file->storeAs($directory, $storedName, self::DISK);

                ChatAttachment::create([
                    'message_id' => $message->id,
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $storedName,
                    'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                    'file_size' => (int) $file->getSize(),
                    'storage_path' => $path,
                ]);

                $this->realtime->increment('attachments_sent');
                $this->realtime->increment('attachment_bytes', (int) $file->getSize());
            }

            $conversation->update(['last_message_at' => now()]);
            DB::table('chat_conversation_participants')
                ->where('conversation_id', $conversation->id)
                ->update(['deleted_at' => null, 'updated_at' => now()]);

            return $message;
        });

        $message->load(['sender:id,name,preferred_name,email', 'attachments']);
        $payload = $this->messagePayload($message);
        $recipientIds = $this->recipientIds($conversation, $userId);

        foreach ($recipientIds as $recipientId) {
            $this->realtime->publish($recipientId, 'message.new', $payload);
        }
        $this->realtime->publish($userId, 'message.sent', $payload);
        $this->realtime->increment('messages_sent');

        AuditService::record('CHAT_MESSAGE_SENT', $message, null, [
            'conversation_id' => $conversation->id,
            'message_type' => $message->message_type,
            'has_attachment' => $message->attachments->isNotEmpty(),
        ]);
        if ($message->attachments->isNotEmpty()) {
            AuditService::record('CHAT_ATTACHMENT_UPLOADED', $message, null, [
                'conversation_id' => $conversation->id,
                'attachments' => $message->attachments->map(fn (ChatAttachment $attachment) => [
                    'attachment_id' => $attachment->id,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                ])->values()->all(),
            ]);
        }

        $otherParticipant = User::query()
            ->whereIn('id', $recipientIds)
            ->orderBy('id')
            ->first();

        app(SystemAlertService::class)->dispatch('chat', 'chat_mensagem_enviada', [
            'conversation' => $conversation->loadMissing('participants:id,name,preferred_name,email'),
            'message' => $message,
            'sender' => $request->user(),
            'recipient' => $otherParticipant,
            'participants' => $conversation->participants ?? [],
            'requester' => $request->user(),
        ]);

        return response()->json($payload, 201);
    }

    public function markRead(Request $request, ChatConversation $conversation): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->authorizeParticipant($conversation, $userId);
        $now = now();

        $senderIds = $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->pluck('sender_id')
            ->unique();

        $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->update(['status' => 'read', 'delivered_at' => $now, 'read_at' => $now, 'updated_at' => $now]);

        DB::table('chat_conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->update(['last_read_at' => $now, 'updated_at' => $now]);

        foreach ($senderIds as $senderId) {
            $this->realtime->publish((int) $senderId, 'message.read', [
                'conversation_id' => $conversation->id,
                'read_by' => $userId,
                'read_at' => $now->toISOString(),
            ]);
        }

        return response()->json(['ok' => true, 'read_at' => $now->toISOString()]);
    }

    public function markDelivered(Request $request, ChatMessage $message): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->authorizeParticipant($message->conversation, $userId);

        if ((int) $message->sender_id === $userId || $message->read_at) {
            return response()->json(['ok' => true]);
        }

        $message->update([
            'status' => 'delivered',
            'delivered_at' => $message->delivered_at ?? now(),
        ]);

        $this->realtime->publish((int) $message->sender_id, 'message.delivered', [
            'conversation_id' => $message->conversation_id,
            'message_id' => $message->id,
            'delivered_at' => $message->delivered_at?->toISOString(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function deleteMessage(Request $request, ChatMessage $message): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->authorizeParticipant($message->conversation, $userId);

        if ((int) $message->sender_id !== $userId && $request->user()->profile !== 'admin') {
            abort(403, 'Você não pode apagar esta mensagem.');
        }

        $message->update(['deleted_at' => now(), 'deleted_by' => $userId]);
        foreach ($this->recipientIds($message->conversation, null) as $recipientId) {
            $this->realtime->publish($recipientId, 'message.deleted', [
                'conversation_id' => $message->conversation_id,
                'message_id' => $message->id,
            ]);
        }

        AuditService::record('CHAT_MESSAGE_DELETED', $message);
        return response()->json(['message' => 'Mensagem apagada.']);
    }

    public function deleteMessages(Request $request): JsonResponse
    {
        $data = $request->validate([
            'message_ids' => ['required', 'array', 'min:1', 'max:100'],
            'message_ids.*' => ['required', 'integer', 'distinct', 'exists:chat_messages,id'],
        ]);
        $userId = (int) $request->user()->id;
        $isAdmin = $request->user()->profile === 'admin';
        $messages = ChatMessage::query()
            ->with('conversation')
            ->whereIn('id', $data['message_ids'])
            ->get();

        foreach ($messages as $message) {
            $this->authorizeParticipant($message->conversation, $userId);
            if ((int) $message->sender_id !== $userId && ! $isAdmin) {
                abort(403, 'Você não pode apagar uma ou mais mensagens selecionadas.');
            }
        }

        DB::transaction(function () use ($messages, $userId) {
            foreach ($messages as $message) {
                if (! $message->deleted_at) {
                    $message->update(['deleted_at' => now(), 'deleted_by' => $userId]);
                }

                foreach ($this->recipientIds($message->conversation, null) as $recipientId) {
                    $this->realtime->publish($recipientId, 'message.deleted', [
                        'conversation_id' => $message->conversation_id,
                        'message_id' => $message->id,
                    ]);
                }

                AuditService::record('CHAT_MESSAGE_DELETED', $message);
            }
        });

        return response()->json([
            'message' => 'Mensagens apagadas.',
            'message_ids' => $messages->pluck('id')->values(),
        ]);
    }

    public function deleteConversation(Request $request, ChatConversation $conversation): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->authorizeParticipant($conversation, $userId);

        DB::table('chat_conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->update(['deleted_at' => now(), 'updated_at' => now()]);

        AuditService::record('CHAT_CONVERSATION_DELETED', $conversation, null, ['user_id' => $userId]);
        return response()->json(['message' => 'Conversa removida da sua lista.']);
    }

    public function typing(Request $request, ChatConversation $conversation): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $this->authorizeParticipant($conversation, $userId);
        $data = $request->validate(['typing' => ['required', 'boolean']]);

        foreach ($this->recipientIds($conversation, $userId) as $recipientId) {
            $this->realtime->publish($recipientId, $data['typing'] ? 'typing.started' : 'typing.stopped', [
                'conversation_id' => $conversation->id,
                'user_id' => $userId,
                'name' => $request->user()->chatDisplayName(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function presence(Request $request): JsonResponse
    {
        $data = $request->validate([
            'state' => ['required', Rule::in(['online', 'away', 'offline'])],
            'path' => ['nullable', 'string', 'max:255'],
            'connection_id' => ['required', 'uuid'],
        ]);
        $userId = (int) $request->user()->id;
        $this->removeStaleConnections();
        $presence = UserPresence::firstOrNew(['user_id' => $userId]);
        $previousStatus = $presence->status;
        $previousConnectionCount = (int) $presence->connection_count;

        if ($data['state'] === 'offline') {
            DB::table('chat_connections')
                ->where('user_id', $userId)
                ->where('connection_id', $data['connection_id'])
                ->delete();
        } else {
            DB::table('chat_connections')->updateOrInsert(
                ['connection_id' => $data['connection_id']],
                [
                    'user_id' => $userId,
                    'status' => $data['state'],
                    'last_seen_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $connectionCount = DB::table('chat_connections')->where('user_id', $userId)->count();
        $aggregateStatus = DB::table('chat_connections')
            ->where('user_id', $userId)
            ->where('status', 'online')
            ->exists() ? 'online' : ($connectionCount > 0 ? 'away' : 'offline');

        $presence->fill([
            'status' => $aggregateStatus,
            'connection_count' => $connectionCount,
            'connected_at' => $connectionCount > 0 ? ($presence->connected_at ?? now()) : null,
            'last_seen_at' => now(),
            'last_path' => $data['path'] ?? null,
        ])->save();

        $onlineConnections = (int) UserPresence::query()->sum('connection_count');
        $this->realtime->increment('connection_events');
        $this->realtime->updatePeakConnections($onlineConnections);

        if ($previousStatus !== $presence->status || $previousConnectionCount !== $presence->connection_count) {
            $this->realtime->publishPresence([
                'user_id' => $userId,
                'presence' => $presence->status,
                'is_online' => $presence->connection_count > 0,
                'last_seen_at' => $presence->last_seen_at?->toISOString(),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function unread(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;
        $conversationIds = DB::table('chat_conversation_participants')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->pluck('conversation_id');

        $byConversation = ChatMessage::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->selectRaw('conversation_id, COUNT(*) as total')
            ->groupBy('conversation_id')
            ->pluck('total', 'conversation_id');

        return response()->json([
            'total' => (int) $byConversation->sum(),
            'conversations' => $byConversation,
        ]);
    }

    public function attachment(Request $request, ChatAttachment $attachment)
    {
        $message = $attachment->message;
        $this->authorizeParticipant($message->conversation, (int) $request->user()->id);

        if (! Storage::disk(self::DISK)->exists($attachment->storage_path)) {
            return response()->json(['message' => 'Arquivo não encontrado.'], 404);
        }

        return Storage::disk(self::DISK)->download(
            $attachment->storage_path,
            $this->safeFilename($attachment->original_name),
            ['Content-Type' => $attachment->mime_type, 'X-Content-Type-Options' => 'nosniff']
        );
    }

    public function dashboard(): JsonResponse
    {
        $this->removeStaleConnections();
        $realtimeConfig = $this->broadcastConfig->publicPayload();
        $isSoketi = ($realtimeConfig['engine'] ?? null) === 'soketi';
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $daily = ChatUsageDaily::whereDate('usage_date', '>=', now()->subDays(29))
            ->orderBy('usage_date')
            ->get();
        $todayUsage = ChatUsageDaily::whereDate('usage_date', $today)->first() ?? new ChatUsageDaily();
        $month = ChatUsageDaily::whereBetween('usage_date', [$monthStart, $today])
            ->selectRaw('SUM(messages_sent) messages_sent, SUM(events_published) events_published, SUM(connection_events) connection_events, MAX(peak_connections) peak_connections, SUM(attachments_sent) attachments_sent, SUM(attachment_bytes) attachment_bytes, SUM(failed_events) failed_events')
            ->first();

        return response()->json([
            'limits' => [
                'daily_messages' => $isSoketi ? null : config('chat.pusher_daily_message_limit'),
                'concurrent_connections' => $isSoketi ? null : config('chat.pusher_connection_limit'),
                'plan' => $isSoketi ? 'Soketi próprio' : 'Pusher Sandbox',
                'engine' => $realtimeConfig['engine'] ?? null,
                'rate_limits' => ChatRealtimeConfig::rateLimits(),
            ],
            'today' => $todayUsage,
            'month' => $month,
            'current' => [
                'connections' => (int) UserPresence::sum('connection_count'),
                'online_users' => UserPresence::where('connection_count', '>', 0)->count(),
                'unread_messages' => ChatMessage::whereNull('read_at')->count(),
            ],
            'totals' => [
                'conversations' => ChatConversation::count(),
                'messages' => ChatMessage::count(),
                'attachments' => ChatAttachment::count(),
                'storage_bytes' => (int) ChatAttachment::sum('file_size'),
            ],
            'daily' => $daily,
        ]);
    }

    private function authorizeParticipant(ChatConversation $conversation, int $userId): void
    {
        $allowed = DB::table('chat_conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->exists();

        abort_unless($allowed, 403, 'Conversa não autorizada.');
    }

    private function recipientIds(ChatConversation $conversation, ?int $exceptUserId): array
    {
        return DB::table('chat_conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->when($exceptUserId, fn ($query) => $query->where('user_id', '!=', $exceptUserId))
            ->pluck('user_id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function conversationPayload(ChatConversation $conversation, int $userId): array
    {
        $other = $conversation->participants->firstWhere('id', '!=', $userId);
        $lastMessage = $conversation->messages->first();
        $unread = $conversation->messages()
            ->where('sender_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();

        return [
            'id' => $conversation->id,
            'type' => $conversation->type,
            'created_at' => $conversation->created_at?->toISOString(),
            'last_message_at' => $conversation->last_message_at?->toISOString(),
            'other_user' => $other ? ['id' => $other->id, 'name' => $other->chatDisplayName(), 'email' => $other->email] : null,
            'last_message' => $lastMessage ? $this->messagePayload($lastMessage) : null,
            'unread_count' => $unread,
        ];
    }

    private function messagePayload(ChatMessage $message): array
    {
        $deleted = $message->deleted_at !== null;

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender' => $message->relationLoaded('sender') && $message->sender
                ? [
                    'id' => $message->sender->id,
                    'name' => $message->sender->chatDisplayName(),
                    'email' => $message->sender->email,
                ]
                : null,
            'body' => $deleted ? null : $message->body,
            'display_body' => $deleted ? 'Mensagem apagada' : $message->body,
            'message_type' => $message->message_type,
            'status' => $message->status,
            'delivered_at' => $message->delivered_at?->toISOString(),
            'read_at' => $message->read_at?->toISOString(),
            'is_deleted' => $deleted,
            'created_at' => $message->created_at?->toISOString(),
            'attachments' => $message->relationLoaded('attachments') && ! $deleted
                ? $message->attachments->map(fn (ChatAttachment $attachment) => [
                    'id' => $attachment->id,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => $attachment->file_size,
                    'download_url' => '/chat/attachments/'.$attachment->id,
                ])->values()
                : [],
        ];
    }

    private function messageType(?string $extension, bool $hasBody): string
    {
        if (! $extension) {
            return 'text';
        }
        if ($hasBody) {
            return 'mixed';
        }
        $extension = strtolower($extension);
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return 'image';
        }
        return $extension === 'pdf' ? 'pdf' : 'txt';
    }

    private function effectiveMaxAttachmentKb(): int
    {
        $configuredKb = max(1, (int) config('chat.max_attachment_kb', 10240));
        $uploadKb = $this->iniSizeToKb(ini_get('upload_max_filesize'));
        $postKb = $this->iniSizeToKb(ini_get('post_max_size'));
        $limits = array_filter([$configuredKb, $uploadKb, $postKb], fn ($value) => (int) $value > 0);

        return (int) (empty($limits) ? $configuredKb : min($limits));
    }

    private function iniSizeToKb(string|false|null $value): int
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        $unit = strtolower(substr($raw, -1));
        $number = (float) $raw;

        return match ($unit) {
            'g' => (int) round($number * 1024 * 1024),
            'm' => (int) round($number * 1024),
            'k' => (int) round($number),
            default => (int) round($number / 1024),
        };
    }

    private function formatAttachmentLimitLabel(int $maxKb): string
    {
        return $maxKb >= 1024
            ? number_format($maxKb / 1024, 0, ',', '.').' MB'
            : number_format($maxKb, 0, ',', '.').' KB';
    }

    private function safeFilename(string $filename): string
    {
        return trim(preg_replace('/[^A-Za-z0-9._ -]/', '', str_replace(['\\', '/'], '-', $filename)) ?: 'arquivo');
    }

    private function removeStaleConnections(): void
    {
        $staleUserIds = DB::table('chat_connections')
            ->where('last_seen_at', '<', now()->subMinutes(2))
            ->pluck('user_id')
            ->unique();

        DB::table('chat_connections')
            ->where('last_seen_at', '<', now()->subMinutes(2))
            ->delete();

        foreach ($staleUserIds as $userId) {
            $count = DB::table('chat_connections')->where('user_id', $userId)->count();
            $presence = UserPresence::where('user_id', $userId)->first();
            UserPresence::where('user_id', $userId)->update([
                'connection_count' => $count,
                'status' => $count > 0 ? 'away' : 'offline',
                'connected_at' => $count > 0 ? DB::raw('connected_at') : null,
                'updated_at' => now(),
            ]);
            if ($presence && ((int) $presence->connection_count !== $count || $presence->status !== ($count > 0 ? 'away' : 'offline'))) {
                $this->realtime->publishPresence([
                    'user_id' => (int) $userId,
                    'presence' => $count > 0 ? 'away' : 'offline',
                    'is_online' => $count > 0,
                    'last_seen_at' => $presence->last_seen_at?->toISOString(),
                ]);
            }
        }
    }
}
