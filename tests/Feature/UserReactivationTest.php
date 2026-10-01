<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserReactivationTest extends TestCase
{
    use RefreshDatabase;

    private function payload(string $cpf, string $email): array
    {
        return [
            'profile' => 'user',
            'name' => 'Nova Usuaria',
            'email' => $email,
            'cpf' => $cpf,
            'password' => 'senha1234',
            'password2' => 'senha1234',
            'active' => true,
        ];
    }

    public function test_cadastro_com_cpf_de_usuario_inativo_reativa_registro_antigo(): void
    {
        AccessProfile::create(['nome' => 'Usuário', 'slug' => 'user', 'ativo' => true]);
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $old = User::factory()->create([
            'profile' => 'admin',
            'cpf' => '52998224725',
            'active' => false,
            'inactive_date' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', $this->payload('52998224725', 'nova@example.com'))
            ->assertOk()
            ->assertJsonPath('status', 'reactivated');

        $old->refresh();
        $this->assertTrue((bool) $old->active);
        $this->assertNull($old->inactive_date);
        $this->assertSame('Nova Usuaria', $old->name);
        $this->assertSame('nova@example.com', $old->email);
        $this->assertSame('user', $old->profile);
        $this->assertSame(1, User::where('cpf', '52998224725')->count());
    }

    public function test_cadastro_com_cpf_de_usuario_ativo_continua_bloqueado(): void
    {
        AccessProfile::create(['nome' => 'Usuário', 'slug' => 'user', 'ativo' => true]);
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        User::factory()->create(['cpf' => '52998224725', 'active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/users', $this->payload('52998224725', 'nova@example.com'))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cpf');
    }
}
