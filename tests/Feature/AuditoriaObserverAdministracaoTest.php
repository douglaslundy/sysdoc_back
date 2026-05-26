<?php

namespace Tests\Feature;

use App\Models\PageCategory;
use App\Models\SystemPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaObserverAdministracaoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_system_page_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $page = SystemPage::create([
            'titulo'    => 'Nova Página',
            'path'      => '/nova-pagina',
            'icone'     => 'file',
            'categoria' => 'Geral',
            'ativo'     => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'SystemPage',
            'model_id'   => $page->id,
        ]);
    }

    public function test_system_page_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $page = SystemPage::create([
            'titulo'    => 'Página Inicial',
            'path'      => '/inicial',
            'icone'     => 'home',
            'categoria' => 'Geral',
            'ativo'     => true,
        ]);

        $page->update(['titulo' => 'Home Page']);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'SystemPage',
            'model_id'   => $page->id,
        ]);
    }

    public function test_system_page_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $page = SystemPage::create([
            'titulo'    => 'Nova Página',
            'path'      => '/nova-pagina',
            'icone'     => 'file',
            'categoria' => 'Geral',
            'ativo'     => true,
        ]);
        $id = $page->id;
        $page->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'SystemPage',
            'model_id'   => $id,
        ]);
    }

    public function test_page_category_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $category = PageCategory::create([
            'nome'   => 'Nova Categoria',
            'icone'  => 'folder',
            'ordem'  => 1,
            'ativo'  => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'PageCategory',
            'model_id'   => $category->id,
        ]);
    }

    public function test_page_category_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $category = PageCategory::create([
            'nome'   => 'Categoria Geral',
            'icone'  => 'folder',
            'ordem'  => 1,
            'ativo'  => true,
        ]);

        $category->update(['nome' => 'Categoria Principal']);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'PageCategory',
            'model_id'   => $category->id,
        ]);
    }

    public function test_page_category_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $category = PageCategory::create([
            'nome'   => 'Categoria a Deletar',
            'icone'  => 'folder',
            'ordem'  => 1,
            'ativo'  => true,
        ]);
        $id = $category->id;
        $category->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'PageCategory',
            'model_id'   => $id,
        ]);
    }
}
