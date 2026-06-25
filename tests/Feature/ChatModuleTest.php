<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Models\AccessProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            ->assertJsonPath('totals.conversations', 1)
            ->assertJsonPath('totals.messages', 1);
    }

    public function test_admin_configura_soketi_com_segredos_criptografados(): void
    {
        $this->sender->update(['password' => Hash::make('SenhaSegura#2026')]);

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
                'current_password' => 'SenhaSegura#2026',
            ])
            ->assertOk()
            ->assertJsonMissing(['app_secret' => 'secret-key-test'])
            ->assertJsonPath('engine', 'soketi')
            ->assertJsonPath('host', 'socket.exemplo.test');

        $raw = DB::table('chat_realtime_configs')->first();
        $this->assertNotSame('sysdoc-chat', $raw->app_id);
        $this->assertNotSame('public-key-test', $raw->app_key);
        $this->assertNotSame('secret-key-test', $raw->app_secret);

        $this->actingAs($this->sender, 'sanctum')
            ->getJson('/api/chat/realtime-config')
            ->assertOk()
            ->assertJsonPath('engine', 'soketi')
            ->assertJsonPath('key', 'public-key-test')
            ->assertJsonPath('host', 'socket.exemplo.test')
            ->assertJsonMissing(['app_secret' => 'secret-key-test']);
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
