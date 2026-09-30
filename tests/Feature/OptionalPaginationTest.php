<?php

namespace Tests\Feature;

use App\Models\QRCodeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\GrantsPages;
use Tests\TestCase;

class OptionalPaginationTest extends TestCase
{
    use GrantsPages;
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grantPages('user', ['/qrcodelogs']);
        $this->user = User::factory()->create(['profile' => 'user', 'active' => true]);
    }

    private function logs(int $quantidade): void
    {
        for ($i = 1; $i <= $quantidade; $i++) {
            QRCodeLog::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'position' => $i,
                'host_name' => 'host',
                'accessed_at' => now()->subMinutes($quantidade - $i),
            ]);
        }
    }

    public function test_qrcode_logs_com_page_devolve_pagina_e_total(): void
    {
        $this->logs(12);

        $resposta = $this->actingAs($this->user, 'sanctum')->getJson('/api/qrcode-logs?page=2&per_page=5');

        $resposta->assertOk()
            ->assertJsonPath('total', 12)
            ->assertJsonPath('current_page', 2)
            ->assertJsonCount(5, 'data');
    }

    public function test_qrcode_logs_por_pagina_tem_teto(): void
    {
        $this->logs(3);

        $resposta = $this->actingAs($this->user, 'sanctum')->getJson('/api/qrcode-logs?page=1&per_page=100000');

        $resposta->assertOk()->assertJsonPath('per_page', 100);
    }

    public function test_qrcode_logs_sem_page_mantem_lista_simples_com_teto(): void
    {
        $this->logs(3);
        config(['pagination.legacy_cap' => 2]);

        $resposta = $this->actingAs($this->user, 'sanctum')->getJson('/api/qrcode-logs');

        $resposta->assertOk()->assertJsonCount(2);
        $this->assertArrayNotHasKey('data', $resposta->json());
    }
}
