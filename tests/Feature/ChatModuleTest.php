<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $this->recipient = User::factory()->create(['active' => true]);
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

    private function startConversation(): ChatConversation
    {
        $response = $this->actingAs($this->sender, 'sanctum')
            ->postJson('/api/chat/conversations', ['user_id' => $this->recipient->id])
            ->assertCreated();

        return ChatConversation::findOrFail($response->json('id'));
    }
}
