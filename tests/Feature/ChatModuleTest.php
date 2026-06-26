<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatRealtimeConfig;
use App\Models\User;
use App\Models\AccessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $sender;

    private User $recipient;

    protected function setUp(): void
    {
        parent::setUp();
        config(['broadcasting.default' => 'null']);
        Storage::fake('private');

        $this->sender = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->recipient = User::factory()->create(['active' => true, 'chat_access_override' => true]);
    }

    public function test_usuario_pode_iniciar_conversa_e_enviar_mensagem(): void
    {
        $conversation = $this->startConversation();

        $response = $this->actingAs($this->sender, 'sanctum')
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", [
                'body' => 'Mensagem de teste',
            ]);

        $response->assertCreated()
            ->assertJsonPath('display_body', 'Mensagem de teste')
            ->assertJsonPath('status', 'sent');

        $this->assertDatabaseHas('chat_messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->sender->id,
            'body' => 'Mensagem de teste',
        ]);
        $this->assertDatabaseHas('chat_usage_daily', ['messages_sent' => 1]);
    }

    public function test_chat_exibe_nome_preferido_quando_preenchido_e_nome_padrao_como_fallback(): void
    {
        $this->recipient->update([
            'name' => 'Maria da Silva',
            'preferred_name' => 'Maria',
        ]);

        $conversation = $this->startConversation();

        $this->actingAs($this->sender, 'sanctum')
            ->getJson('/api/chat/conversations')
            ->assertOk()
            ->assertJsonPath('0.other_user.name', 'Maria');

        $this->recipient->update([
            'preferred_name' => null,
        ]);

        $this->actingAs($this->sender, 'sanctum')
            ->getJson('/api/chat/conversations')
            ->assertOk()
            ->assertJsonPath('0.other_user.name', 'Maria da Silva');
    }

    public function test_usuario_nao_pode_acessar_conversa_de_terceiros(): void
    {
        $conversation = $this->startConversation();
        $intruder = User::factory()->create(['active' => true]);

        $this->actingAs($intruder, 'sanctum')
            ->getJson("/api/chat/conversations/{$conversation->id}/messages")
            ->assertForbidden();
    }

    public function test_leitura_e_exclusao_de_mensagem_sao_persistidas(): void
    {
        $conversation = $this->startConversation();
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->sender->id,
            'body' => 'Leia e apague',
            'message_type' => 'text',
            'status' => 'sent',
        ]);

        $this->actingAs($this->recipient, 'sanctum')
            ->postJson("/api/chat/conversations/{$conversation->id}/read")
            ->assertOk();

        $this->assertDatabaseHas('chat_messages', [
            'id' => $message->id,
            'status' => 'read',
        ]);

        $this->actingAs($this->sender, 'sanctum')
            ->deleteJson("/api/chat/messages/{$message->id}")
            ->assertOk();

        $this->assertDatabaseMissing('chat_messages', [
            'id' => $message->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('chat_messages', ['id' => $message->id]);
    }

    public function test_upload_invalido_e_bloqueado_no_backend(): void
    {
        $conversation = $this->startConversation();

        $this->actingAs($this->sender, 'sanctum')
            ->post("/api/chat/conversations/{$conversation->id}/messages", [
                'file' => UploadedFile::fake()->create('programa.exe', 10, 'application/octet-stream'),
            ], ['Accept' => 'application/json'])
            ->assertStatus(422);

        $this->assertDatabaseCount('chat_attachments', 0);
    }

    public function test_usuario_exclui_multiplas_mensagens_proprias(): void
    {
        $conversation = $this->startConversation();
        $messages = collect(['Primeira', 'Segunda'])->map(fn ($body) => ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->sender->id,
            'body' => $body,
            'message_type' => 'text',
            'status' => 'sent',
        ]));

        $this->actingAs($this->sender, 'sanctum')
            ->deleteJson('/api/chat/messages', [
                'message_ids' => $messages->pluck('id')->all(),
            ])
            ->assertOk()
            ->assertJsonCount(2, 'message_ids');

        foreach ($messages as $message) {
            $this->assertNotNull($message->fresh()->deleted_at);
            $this->assertSame($this->sender->id, $message->fresh()->deleted_by);
        }
    }

    public function test_usuario_nao_exclui_em_lote_mensagem_de_outro_remetente(): void
    {
        $conversation = $this->startConversation();
        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->sender->id,
            'body' => 'Mensagem recebida',
            'message_type' => 'text',
            'status' => 'sent',
        ]);

        $this->actingAs($this->recipient, 'sanctum')
            ->deleteJson('/api/chat/messages', ['message_ids' => [$message->id]])
            ->assertForbidden();

        $this->assertNull($message->fresh()->deleted_at);
    }

    public function test_dashboard_do_chat_exibe_consumo_e_totais(): void
    {
        $conversation = $this->startConversation();
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'sender_id' => $this->sender->id,
            'body' => 'Metrica',
            'message_type' => 'text',
            'status' => 'sent',
        ]);

        $this->actingAs($this->sender, 'sanctum')
            ->getJson('/api/dashboard/chat')
            ->assertOk()
            ->assertJsonPath('limits.concurrent_connections', 100)
            ->assertJsonPath('limits.rate_limits.rate_limit_global', 300)
            ->assertJsonPath('totals.conversations', 1)
            ->assertJsonPath('totals.messages', 1);
    }

    public function test_admin_configura_soketi_com_segredos_criptografados(): void
    {
        $this->actingAs($this->sender, 'sanctum')
            ->putJson('/api/chat/config', [
                'engine' => 'soketi',
                'active' => true,
                'app_id' => 'sysdoc-chat',
                'app_key' => 'public-key-test',
                'app_secret' => 'secret-key-test',
                'host' => 'wss://socket.exemplo.test',
                'port' => 443,
                'scheme' => 'https',
                'use_tls' => true,
                'auto_open_on_message' => true,
                'play_sound_on_message' => false,
            ])
            ->assertOk()
            ->assertJsonMissing(['app_secret' => 'secret-key-test'])
            ->assertJsonPath('configured', true)
            ->assertJsonPath('engine', 'soketi')
            ->assertJsonPath('host', 'socket.exemplo.test')
            ->assertJsonPath('rate_limit_global', 300)
            ->assertJsonPath('rate_limit_messages', 30)
            ->assertJsonPath('auto_open_on_message', true)
            ->assertJsonPath('play_sound_on_message', false);

        $raw = DB::table('chat_realtime_configs')->first();
        $this->assertNotSame('sysdoc-chat', $raw->app_id);
        $this->assertNotSame('public-key-test', $raw->app_key);
        $this->assertNotSame('secret-key-test', $raw->app_secret);

        $this->actingAs($this->sender, 'sanctum')
            ->putJson('/api/chat/config', [
                'engine' => 'soketi',
                'active' => true,
                'app_key' => 'public-key-updated',
                'host' => 'socket.exemplo.test',
                'port' => 443,
                'scheme' => 'https',
                'use_tls' => true,
            ])
            ->assertOk()
            ->assertJsonPath('configured', true);

        $this->assertSame('sysdoc-chat', ChatRealtimeConfig::current()->app_id);
        $this->assertSame('public-key-updated', ChatRealtimeConfig::current()->app_key);
        $this->assertSame('secret-key-test', ChatRealtimeConfig::current()->app_secret);

        $this->actingAs($this->sender, 'sanctum')
            ->getJson('/api/chat/realtime-config')
            ->assertOk()
            ->assertJsonPath('engine', 'soketi')
            ->assertJsonPath('key', 'public-key-updated')
            ->assertJsonPath('host', 'socket.exemplo.test')
            ->assertJsonPath('auto_open_on_message', true)
            ->assertJsonPath('play_sound_on_message', false)
            ->assertJsonMissingPath('app_id')
            ->assertJsonMissingPath('app_secret')
            ->assertJsonMissing(['app_secret' => 'secret-key-test']);
    }

    public function test_admin_ativa_desativa_e_apaga_credenciais_sem_confirmar_senha(): void
    {
        $this->actingAs($this->sender, 'sanctum')
            ->putJson('/api/chat/config', [
                'engine' => 'pusher',
                'active' => false,
                'app_id' => '123456',
                'app_key' => 'public-key-test',
                'app_secret' => 'secret-key-test',
                'cluster' => 'mt1',
                'use_tls' => true,
            ])
            ->assertOk()
            ->assertJsonPath('configured', true)
            ->assertJsonPath('active', false);

        $this->actingAs($this->sender, 'sanctum')
            ->patchJson('/api/chat/config/status', ['active' => true])
            ->assertOk()
            ->assertJsonPath('active', true);

        $this->actingAs($this->sender, 'sanctum')
            ->patchJson('/api/chat/config/status', ['active' => false])
            ->assertOk()
            ->assertJsonPath('active', false);

        $this->actingAs($this->sender, 'sanctum')
            ->deleteJson('/api/chat/config')
            ->assertOk()
            ->assertJsonPath('configured', false)
            ->assertJsonPath('active', false);
    }

    public function test_rota_de_teste_nao_bloqueia_cinco_tentativas_administrativas(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->actingAs($this->sender, 'sanctum')
                ->postJson('/api/chat/config/test', [
                    'engine' => 'pusher',
                    'cluster' => 'mt1',
                ])
                ->assertStatus(422)
                ->assertJsonPath('message', 'Informe App ID, App Key e App Secret.');
        }
    }

    public function test_admin_pode_gerenciar_limites_do_chat(): void
    {
        $this->actingAs($this->sender, 'sanctum')
            ->putJson('/api/chat/config', [
                'engine' => 'pusher',
                'active' => false,
                'cluster' => 'mt1',
                'rate_limit_decay_minutes' => 3,
                'rate_limit_global' => 450,
                'rate_limit_sync' => 180,
                'rate_limit_messages' => 40,
                'rate_limit_typing' => 90,
                'rate_limit_presence' => 75,
            ])
            ->assertOk()
            ->assertJsonPath('rate_limit_decay_minutes', 3)
            ->assertJsonPath('rate_limit_global', 450)
            ->assertJsonPath('rate_limit_sync', 180)
            ->assertJsonPath('rate_limit_messages', 40)
            ->assertJsonPath('rate_limit_typing', 90)
            ->assertJsonPath('rate_limit_presence', 75);

        $config = ChatRealtimeConfig::current();
        $this->assertSame(3, $config->rate_limit_decay_minutes);
        $this->assertSame(450, $config->rate_limit_global);
        $this->assertSame(180, $config->rate_limit_sync);
        $this->assertSame(40, $config->rate_limit_messages);
        $this->assertSame(90, $config->rate_limit_typing);
        $this->assertSame(75, $config->rate_limit_presence);
    }

    public function test_admin_pode_gerenciar_comportamento_de_abertura_e_som(): void
    {
        $this->actingAs($this->sender, 'sanctum')
            ->putJson('/api/chat/config', [
                'engine' => 'pusher',
                'active' => false,
                'cluster' => 'mt1',
                'auto_open_on_message' => true,
                'play_sound_on_message' => false,
            ])
            ->assertOk()
            ->assertJsonPath('auto_open_on_message', true)
            ->assertJsonPath('play_sound_on_message', false);

        $config = ChatRealtimeConfig::current();
        $this->assertTrue((bool) $config->auto_open_on_message);
        $this->assertFalse((bool) $config->play_sound_on_message);

        $this->actingAs($this->sender, 'sanctum')
            ->getJson('/api/chat/realtime-config')
            ->assertOk()
            ->assertJsonPath('auto_open_on_message', true)
            ->assertJsonPath('play_sound_on_message', false);
    }

    public function test_limite_de_mensagens_respeita_configuracao_dinamica(): void
    {
        ChatRealtimeConfig::current()->update([
            'rate_limit_global' => 500,
            'rate_limit_messages' => 2,
            'rate_limit_decay_minutes' => 1,
        ]);

        $conversation = $this->startConversation();

        $this->actingAs($this->sender, 'sanctum')
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", ['body' => 'primeira'])
            ->assertCreated();

        $this->actingAs($this->sender, 'sanctum')
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", ['body' => 'segunda'])
            ->assertCreated();

        $this->actingAs($this->sender, 'sanctum')
            ->postJson("/api/chat/conversations/{$conversation->id}/messages", ['body' => 'terceira'])
            ->assertStatus(429);
    }

    public function test_usuario_comum_nao_acessa_configuracao_administrativa_do_chat(): void
    {
        $this->actingAs($this->recipient, 'sanctum')
            ->getJson('/api/chat/config')
            ->assertForbidden();
    }

    public function test_perfil_e_excecao_individual_controlam_acesso_ao_chat(): void
    {
        $profile = AccessProfile::create([
            'nome' => 'Chat permitido',
            'slug' => 'chatok',
            'ativo' => true,
            'chat_enabled' => true,
        ]);
        $allowed = User::factory()->create(['profile' => $profile->slug, 'active' => true]);
        $blocked = User::factory()->create([
            'profile' => $profile->slug,
            'active' => true,
            'chat_access_override' => false,
        ]);

        $this->actingAs($allowed, 'sanctum')->getJson('/api/chat/users')->assertOk();
        $this->actingAs($blocked, 'sanctum')->getJson('/api/chat/users')->assertForbidden();
    }

    private function startConversation(): ChatConversation
    {
        $response = $this->actingAs($this->sender, 'sanctum')
            ->postJson('/api/chat/conversations', ['user_id' => $this->recipient->id])
            ->assertCreated();

        return ChatConversation::findOrFail($response->json('id'));
    }
}
