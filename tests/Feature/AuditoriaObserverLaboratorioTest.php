<?php

namespace Tests\Feature;

use App\Models\Exame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaObserverLaboratorioTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_exame_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $exame = Exame::create(['nome' => 'Hemograma', 'codigo' => 'HEM-001', 'ativo' => true]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'Exame',
            'model_id'   => $exame->id,
        ]);
    }

    public function test_exame_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $exame = Exame::create(['nome' => 'Hemograma', 'codigo' => 'HEM-002', 'ativo' => true]);
        $exame->update(['nome' => 'Hemograma Completo']);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'Exame',
            'model_id'   => $exame->id,
        ]);
    }

    public function test_exame_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $exame = Exame::create(['nome' => 'Hemograma', 'codigo' => 'HEM-003', 'ativo' => true]);
        $id    = $exame->id;
        $exame->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'Exame',
            'model_id'   => $id,
        ]);
    }
}
