<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_envia_link_de_redefinicao_para_usuario_ativo(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'teste@example.com',
            'active' => true,
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Se o e-mail estiver cadastrado, voce recebera o link de redefinicao.',
            ]);

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_redefine_senha_quando_token_e_senha_sao_validos(): void
    {
        $user = User::factory()->create([
            'email' => 'troca@example.com',
            'active' => true,
            'password' => Hash::make('senhaantiga123'),
        ]);

        $token = 'token-de-teste-123';

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'nova-senha-123',
            'password_confirmation' => 'nova-senha-123',
        ]);

        $response->assertOk()
            ->assertJson([
                'message' => 'Senha redefinida com sucesso! Faca login.',
            ]);

        $this->assertTrue(Hash::check('nova-senha-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => $user->email,
        ]);
    }

    public function test_rejeita_senha_com_menos_de_8_caracteres(): void
    {
        $user = User::factory()->create([
            'email' => 'curta@example.com',
            'active' => true,
        ]);

        $token = 'token-curto-123';

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => '1234567',
            'password_confirmation' => '1234567',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
