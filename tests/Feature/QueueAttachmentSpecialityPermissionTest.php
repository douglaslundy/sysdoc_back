<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\Client;
use App\Models\QueueAttachment;
use App\Models\Speciality;
use App\Models\SystemPage;
use App\Models\User;
use App\Models\UserSpecialityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class QueueAttachmentSpecialityPermissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $limited;

    private Speciality $fisio;

    private Speciality $fono;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $profile = AccessProfile::create([
            'nome' => 'Usuario Fila',
            'slug' => 'user',
            'ativo' => true,
        ]);
        $page = SystemPage::firstOrCreate(
            ['path' => '/queue'],
            ['titulo' => 'Fila', 'ativo' => true]
        );
        $profile->pages()->attach($page->id);

        $this->limited = User::factory()->create(['profile' => 'user', 'active' => true]);
        $this->fisio = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fisioterapia']);
        $this->fono = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fonoaudiologia']);

        $this->client = Client::create([
            'name' => 'Paciente Teste',
            'mother' => 'Mae Teste',
            'cpf' => '123.456.789-00',
            'born_date' => '1990-01-01',
            'active' => true,
        ]);

        // $limited só possui can_view/can_edit/can_insert em Fisioterapia; nenhum acesso a Fonoaudiologia.
        UserSpecialityPermission::create([
            'user_id' => $this->limited->id,
            'speciality_id' => $this->fisio->id,
            'can_view' => true,
            'can_edit' => true,
            'can_insert' => true,
        ]);
    }

    private function insertQueueRow(Speciality $speciality): int
    {
        DB::table('queue')->insert([
            'uuid' => (string) Str::uuid(),
            'id_client' => $this->client->id,
            'id_specialities' => $speciality->id,
            'id_user' => $this->admin->id,
            'done' => false,
            'urgency' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('queue')->max('id');
    }

    private function createAttachment(int $queueId): int
    {
        return QueueAttachment::create([
            'queue_id' => $queueId,
            'uploaded_by' => $this->admin->id,
            'disk' => 'private',
            'path' => 'queue-attachments/'.$queueId.'/arquivo.pdf',
            'original_name' => 'arquivo.pdf',
            'mime_type' => 'application/pdf',
            'size_bytes' => 100,
        ])->id;
    }

    public function test_index_retorna_403_para_especialidade_sem_can_view(): void
    {
        $queueId = $this->insertQueueRow($this->fono);

        $response = $this->actingAs($this->limited, 'sanctum')
            ->getJson("/api/queues/{$queueId}/attachments");

        $response->assertStatus(403);
    }

    public function test_download_retorna_403_para_especialidade_sem_can_view(): void
    {
        Storage::fake('private');

        $queueId = $this->insertQueueRow($this->fono);
        $attachmentId = $this->createAttachment($queueId);

        $response = $this->actingAs($this->limited, 'sanctum')
            ->getJson("/api/queues/{$queueId}/attachments/{$attachmentId}/download");

        $response->assertStatus(403);
    }

    public function test_store_retorna_403_para_especialidade_sem_can_edit(): void
    {
        Storage::fake('private');

        $queueId = $this->insertQueueRow($this->fono);

        $response = $this->actingAs($this->limited, 'sanctum')
            ->postJson("/api/queues/{$queueId}/attachments", [
                'files' => [
                    UploadedFile::fake()->create('exame.pdf', 50, 'application/pdf'),
                ],
            ]);

        $response->assertStatus(403);
    }

    public function test_destroy_retorna_403_para_especialidade_sem_can_edit(): void
    {
        Storage::fake('private');

        $queueId = $this->insertQueueRow($this->fono);
        $attachmentId = $this->createAttachment($queueId);

        $response = $this->actingAs($this->limited, 'sanctum')
            ->deleteJson("/api/queues/{$queueId}/attachments/{$attachmentId}");

        $response->assertStatus(403);
    }
}
