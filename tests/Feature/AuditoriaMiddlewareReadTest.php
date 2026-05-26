<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaMiddlewareReadTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_acesso_monitor_aps_grava_audit_read(): void
    {
        // Testa apenas que o middleware grava READ — o endpoint pode falhar por conexão
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/monitor-aps/indicadores/resumo?ano=2025&mes=1');
            // Não asserimos o status porque o banco eSUS pode não estar disponível em teste

        $this->assertDatabaseHas('audit_logs', [
            'action'  => 'READ',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_filtros_sao_gravados_em_new_values(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/monitor-aps/indicadores/resumo?ano=2025&mes=3&ine=0000123');

        $log = \App\Models\AuditLog::where('action', 'READ')
            ->where('user_id', $this->admin->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('2025', $log->new_values['ano'] ?? null);
        $this->assertEquals('3', $log->new_values['mes'] ?? null);
    }
}
