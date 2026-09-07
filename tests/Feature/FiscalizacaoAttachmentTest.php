<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FiscalizacaoAttachmentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Fiscalizacao $fiscalizacao;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $estabelecimento = Estabelecimento::factory()->create();
        $this->fiscalizacao = Fiscalizacao::create([
            'estabelecimento_id' => $estabelecimento->id,
            'fiscal_id' => $this->admin->id,
            'data_visita' => '2026-09-06',
            'resultado' => 'Conforme',
        ]);
    }

    public function test_upload_lista_e_remove_anexo(): void
    {
        Storage::fake('private');

        $upload = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/attachments", [
                'files' => [UploadedFile::fake()->create('foto.jpg', 500, 'image/jpeg')],
            ]);

        $upload->assertStatus(201)
            ->assertJsonStructure(['message', 'attachments' => [['id', 'original_name', 'mime_type']]]);

        $attachmentId = $upload->json('attachments.0.id');
        $storedPath = $upload->json('attachments.0.path');
        Storage::disk('private')->assertExists($storedPath);

        $list = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/attachments");
        $list->assertStatus(200);
        $this->assertCount(1, $list->json());

        $delete = $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/attachments/{$attachmentId}");
        $delete->assertStatus(200);
        Storage::disk('private')->assertMissing($storedPath);
    }

    public function test_upload_sem_permissao_de_pagina_retorna_403(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);
        Storage::fake('private');

        $response = $this->actingAs($user, 'sanctum')
            ->postJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/attachments", [
                'files' => [UploadedFile::fake()->create('foto.jpg', 500, 'image/jpeg')],
            ]);

        $response->assertStatus(403);
    }

    public function test_listar_anexos_sem_permissao_de_pagina_retorna_403(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/fiscalizacoes/{$this->fiscalizacao->id}/attachments");

        $response->assertStatus(403);
    }
}
