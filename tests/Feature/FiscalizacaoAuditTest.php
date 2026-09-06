<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FiscalizacaoAuditTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Estabelecimento $estabelecimento;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->estabelecimento = Estabelecimento::factory()->create();
    }

    public function test_fiscalizacao_create_grava_audit_log_e_navega_relacionamentos(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $fiscalizacao = Fiscalizacao::create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'fiscal_id' => $this->admin->id,
            'data_visita' => '2026-09-06',
            'resultado' => 'Conforme',
            'observacoes' => 'Tudo em ordem.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREATE',
            'model_type' => 'Fiscalizacao',
            'model_id' => $fiscalizacao->id,
        ]);

        $this->assertTrue($fiscalizacao->estabelecimento->is($this->estabelecimento));
        $this->assertTrue($fiscalizacao->fiscal->is($this->admin));
    }

    public function test_fiscalizacao_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $fiscalizacao = Fiscalizacao::create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'fiscal_id' => $this->admin->id,
            'data_visita' => '2026-09-06',
            'resultado' => 'Conforme',
        ]);

        $fiscalizacao->update(['resultado' => 'Não conforme']);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'UPDATE',
            'model_type' => 'Fiscalizacao',
            'model_id' => $fiscalizacao->id,
        ]);
    }

    public function test_fiscalizacao_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $fiscalizacao = Fiscalizacao::create([
            'estabelecimento_id' => $this->estabelecimento->id,
            'fiscal_id' => $this->admin->id,
            'data_visita' => '2026-09-06',
            'resultado' => 'Conforme',
        ]);

        $fiscalizacao->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'DELETE',
            'model_type' => 'Fiscalizacao',
            'model_id' => $fiscalizacao->id,
        ]);
    }
}
