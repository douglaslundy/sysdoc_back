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

    public function test_abertura_de_pagina_grava_audit_view(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/audit/page-view', [
                'path' => '/monitor-aps',
                'label' => 'Monitor APS',
            ])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'action'  => 'VIEW',
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_filtros_sao_gravados_em_new_values(): void
    {
        $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/audit/page-view', [
                'path' => '/monitor-aps',
                'label' => 'Monitor APS',
                'filtros' => [
                    'ano' => '2025',
                    'mes' => '3',
                    'ine' => '0000123',
                ],
            ])
            ->assertOk();

        $log = \App\Models\AuditLog::where('action', 'READ')
            ->where('user_id', $this->admin->id)
            ->latest()
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('2025', $log->new_values['filtros']['ano'] ?? null);
        $this->assertEquals('3', $log->new_values['filtros']['mes'] ?? null);
    }
}
