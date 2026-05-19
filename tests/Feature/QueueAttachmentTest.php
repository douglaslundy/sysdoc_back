<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private int $queueId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $client = Client::create([
            'name' => 'Paciente Teste',
            'mother' => 'Mae Teste',
            'cpf' => '123.456.789-00',
            'born_date' => '1990-01-01',
            'sexo' => 'MASCULINE',
            'active' => true,
        ]);

        $speciality = Speciality::create([
            'id_user' => $this->user->id,
            'name' => 'Cardiologia',
        ]);

        DB::table('queue')->insert([
            'uuid' => (string) Str::uuid(),
            'id_client' => $client->id,
            'id_specialities' => $speciality->id,
            'id_user' => $this->user->id,
            'done' => false,
            'urgency' => false,
            'obs' => 'Fila teste',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->queueId = (int) DB::table('queue')->max('id');
    }

    public function test_upload_lista_e_remove_anexo_da_fila(): void
    {
        Storage::fake('private');

        $upload = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/queues/{$this->queueId}/attachments", [
                'files' => [
                    UploadedFile::fake()->create('exame.pdf', 50, 'application/pdf'),
                ],
            ]);

        $upload->assertStatus(201)
            ->assertJsonStructure(['message', 'attachments' => [['id', 'original_name', 'mime_type']]]);

        $attachmentId = $upload->json('attachments.0.id');
        $storedPath = $upload->json('attachments.0.path');

        $this->assertNotNull($attachmentId);
        Storage::disk('private')->assertExists($storedPath);

        $list = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/queues/{$this->queueId}/attachments");

        $list->assertStatus(200);
        $this->assertCount(1, $list->json());
        $this->assertSame('exame.pdf', $list->json('0.original_name'));

        $delete = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/queues/{$this->queueId}/attachments/{$attachmentId}");

        $delete->assertStatus(200)
            ->assertJsonPath('message', 'Anexo removido com sucesso.');

        Storage::disk('private')->assertMissing($storedPath);
    }
}
