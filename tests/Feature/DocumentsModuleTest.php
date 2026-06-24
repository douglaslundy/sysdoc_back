<?php

namespace Tests\Feature;

use App\Models\DocumentApproval;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentsModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedAccessPages();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->manager = User::factory()->create(['profile' => 'manager', 'active' => true]);
        $this->regularUser = User::factory()->create(['profile' => 'user', 'active' => true]);
    }

    public function test_usuario_sem_permissao_nao_acessa_o_modulo(): void
    {
        $this->actingAs($this->regularUser, 'sanctum')
            ->getJson('/api/documentos')
            ->assertStatus(403);
    }

    public function test_usuario_com_permissao_cria_documento_e_nova_versao(): void
    {
        Storage::fake('private');

        $type = DocumentType::create([
            'codigo' => 'oficio',
            'nome' => 'Ofício',
            'descricao' => 'Teste',
            'ordem' => 1,
            'ativo' => true,
        ]);

        $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/documentos/tipos')
            ->assertStatus(200);

        $create = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/documentos', [
                'document_type_id' => $type->id,
                'titulo' => 'Documento de Teste',
                'resumo' => 'Resumo',
                'sigilo' => 'publico',
                'arquivo' => UploadedFile::fake()->create('teste.pdf', 50, 'application/pdf'),
            ]);

        $create->assertStatus(201);

        $documentId = $create->json('id');
        $versionId = $create->json('latest_version.id') ?: $create->json('latestVersion.id');

        $this->assertNotNull($documentId);
        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'titulo' => 'Documento de Teste',
        ]);

        $versionRow = DB::table('document_versions')->where('document_id', $documentId)->first();
        $this->assertNotNull($versionRow);
        Storage::disk('private')->assertExists($versionRow->path);

        $update = $this->actingAs($this->manager, 'sanctum')
            ->postJson("/api/documentos/{$documentId}/versoes", [
                'arquivo' => UploadedFile::fake()->create('teste-versao-2.pdf', 60, 'application/pdf'),
            ]);

        $update->assertStatus(200);
        $this->assertDatabaseCount('document_versions', 2);
        $this->assertDatabaseHas('documents', [
            'id' => $documentId,
            'current_version_number' => 2,
        ]);
    }

    public function test_exclusao_restrita_cria_aprovacao_formal(): void
    {
        Storage::fake('private');

        $type = DocumentType::create([
            'codigo' => 'relatorio',
            'nome' => 'Relatório',
            'descricao' => 'Teste',
            'ordem' => 1,
            'ativo' => true,
        ]);

        $create = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/documentos', [
                'document_type_id' => $type->id,
                'titulo' => 'Documento Restrito',
                'resumo' => 'Resumo',
                'sigilo' => 'restrito',
                'arquivo' => UploadedFile::fake()->create('restrito.pdf', 50, 'application/pdf'),
            ]);

        $create->assertStatus(201);
        $documentId = $create->json('id');

        $delete = $this->actingAs($this->manager, 'sanctum')
            ->deleteJson("/api/documentos/{$documentId}", [
                'assinaturas' => [$this->manager->id, $this->admin->id, $this->regularUser->id],
            ]);

        $delete->assertStatus(200)
            ->assertJsonPath('message', 'Documento removido com sucesso.');

        $this->assertSoftDeleted('documents', ['id' => $documentId]);

        $approval = DocumentApproval::query()->where('document_id', $documentId)->first();
        $this->assertNotNull($approval);
        $this->assertSame('delete', $approval->action);
        $this->assertSame('approved', $approval->status);
        $this->assertSame(3, $approval->signer_count);
        $this->assertContains($this->manager->id, $approval->signer_user_ids);
        $this->assertContains($this->admin->id, $approval->signer_user_ids);
        $this->assertContains($this->regularUser->id, $approval->signer_user_ids);

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/documentos/aprovacoes')
            ->assertStatus(200)
            ->assertJsonFragment(['document_id' => $documentId]);
    }

    private function seedAccessPages(): void
    {
        DB::table('access_profiles')->insert([
            ['nome' => 'Administrador', 'slug' => 'admin', 'descricao' => 'Admin', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Gerente', 'slug' => 'manager', 'descricao' => 'Gerente', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['nome' => 'Usuário', 'slug' => 'user', 'descricao' => 'Usuário', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('system_pages')->insert([
            ['titulo' => 'Documentos', 'path' => '/documentos', 'icone' => 'file-text', 'categoria' => 'Documentos', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['titulo' => 'Tipos de Documentos', 'path' => '/documentos/tipos', 'icone' => 'list', 'categoria' => 'Documentos', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
            ['titulo' => 'Aprovações', 'path' => '/documentos/aprovacoes', 'icone' => 'check-circle', 'categoria' => 'Documentos', 'ativo' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $pages = DB::table('system_pages')->pluck('id', 'path');
        $profiles = DB::table('access_profiles')->pluck('id', 'slug');

        DB::table('profile_page_permissions')->insert([
            ['access_profile_id' => $profiles['admin'], 'system_page_id' => $pages['/documentos'], 'created_at' => now(), 'updated_at' => now()],
            ['access_profile_id' => $profiles['admin'], 'system_page_id' => $pages['/documentos/tipos'], 'created_at' => now(), 'updated_at' => now()],
            ['access_profile_id' => $profiles['admin'], 'system_page_id' => $pages['/documentos/aprovacoes'], 'created_at' => now(), 'updated_at' => now()],
            ['access_profile_id' => $profiles['manager'], 'system_page_id' => $pages['/documentos'], 'created_at' => now(), 'updated_at' => now()],
            ['access_profile_id' => $profiles['manager'], 'system_page_id' => $pages['/documentos/tipos'], 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
