<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    private function bearer(User $user): array
    {
        return ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken];
    }

    public function test_usuarios_diferentes_no_mesmo_ip_nao_compartilham_o_limite_da_api(): void
    {
        $a = User::factory()->create(['active' => true]);
        $b = User::factory()->create(['active' => true]);

        // 150 requisicoes do usuario A — acima do limite antigo (120) que era
        // compartilhado por IP porque o rate limiter caia no fallback de IP
        // (o guard default e JWT e nao le o token Sanctum do frontend).
        $last = null;
        for ($i = 0; $i < 150; $i++) {
            $last = $this->withHeaders($this->bearer($a))->getJson('/api/user');
            if ($last->status() === 429) {
                break;
            }
        }

        $last->assertOk();

        // O usuario B, no mesmo IP de teste, tem o proprio balde e nao foi
        // afetado pelo trafego do A.
        $this->withHeaders($this->bearer($b))->getJson('/api/user')->assertOk();
    }

    public function test_um_unico_usuario_ainda_tem_teto_para_conter_abuso(): void
    {
        $user = User::factory()->create(['active' => true]);
        $headers = $this->bearer($user);

        $status = 200;
        for ($i = 0; $i < 340; $i++) {
            $status = $this->withHeaders($headers)->getJson('/api/user')->status();
            if ($status === 429) {
                break;
            }
        }

        // Continua havendo um limite (300/min) — so que agora por usuario.
        $this->assertSame(429, $status);
    }
}
