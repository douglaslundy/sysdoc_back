<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaObserverEstabelecimentosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_estabelecimento_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $estab = Estabelecimento::create([
            'nome_responsavel'      => 'João Silva',
            'nome_estabelecimento'  => 'Farmácia Central',
            'endereco'              => 'Rua das Flores, 123',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'Estabelecimento',
            'model_id'   => $estab->id,
        ]);
    }

    public function test_estabelecimento_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $estab = Estabelecimento::create([
            'nome_responsavel'      => 'João Silva',
            'nome_estabelecimento'  => 'Farmácia Central',
            'endereco'              => 'Rua das Flores, 123',
        ]);

        $estab->update(['nome_estabelecimento' => 'Farmácia Nova']);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'Estabelecimento',
            'model_id'   => $estab->id,
        ]);
    }

    public function test_estabelecimento_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $estab = Estabelecimento::create([
            'nome_responsavel'      => 'João Silva',
            'nome_estabelecimento'  => 'Farmácia Central',
            'endereco'              => 'Rua das Flores, 123',
        ]);
        $id = $estab->id;
        $estab->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'Estabelecimento',
            'model_id'   => $id,
        ]);
    }
}
