<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\PageCategory;
use App\Models\ProtocolType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regras de FormRequest que dependem do id da rota (unique ... ignore): salvar o próprio registro
 * sem mudar o valor único precisa passar; colidir com outro registro precisa dar 422.
 */
class FormRequestRouteParamRulesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    private function chamar(string $method, string $uri, array $data = [])
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($this->admin(), 'sanctum')->json($method, $uri, $data);
    }

    public function test_perfil_pode_ser_salvo_com_o_proprio_nome_e_slug_mas_nao_com_os_de_outro(): void
    {
        $a = AccessProfile::create(['nome' => 'Perfil A', 'slug' => 'perfil-a', 'ativo' => true]);
        AccessProfile::create(['nome' => 'Perfil B', 'slug' => 'perfil-b', 'ativo' => true]);

        $this->chamar('PUT', "/api/access-profiles/{$a->id}", ['nome' => 'Perfil A', 'slug' => 'perfil-a'])
            ->assertStatus(200);

        $this->chamar('PUT', "/api/access-profiles/{$a->id}", ['nome' => 'Perfil B'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nome');
    }

    public function test_categoria_de_pagina_pode_ser_salva_com_o_proprio_nome_mas_nao_com_o_de_outra(): void
    {
        $a = PageCategory::create(['nome' => 'Categoria A', 'ordem' => 1, 'ativo' => true]);
        PageCategory::create(['nome' => 'Categoria B', 'ordem' => 2, 'ativo' => true]);

        $this->chamar('PUT', "/api/page-categories/{$a->id}", ['nome' => 'Categoria A'])->assertStatus(200);

        $this->chamar('PUT', "/api/page-categories/{$a->id}", ['nome' => 'Categoria B'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('nome');
    }

    public function test_tipo_de_protocolo_pode_ser_salvo_com_o_proprio_codigo_mas_nao_com_o_de_outro(): void
    {
        $a = ProtocolType::create(['codigo' => 'tipo_a', 'nome' => 'Tipo A', 'ordem' => 1, 'ativo' => true]);
        ProtocolType::create(['codigo' => 'tipo_b', 'nome' => 'Tipo B', 'ordem' => 2, 'ativo' => true]);

        $this->chamar('PUT', "/api/protocolos/tipos/{$a->id}", ['codigo' => 'tipo_a', 'nome' => 'Tipo A2'])
            ->assertStatus(200);

        $this->chamar('PUT', "/api/protocolos/tipos/{$a->id}", ['codigo' => 'tipo_b'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('codigo');
    }

    public function test_alerta_de_protocolo_exige_canais_validos(): void
    {
        $base = ['nome' => 'Alerta X', 'modulo' => 'protocolo', 'gatilho' => 'novo'];

        $this->chamar('POST', '/api/protocolos/alertas', $base)
            ->assertStatus(422)
            ->assertJsonValidationErrors('canais');

        $this->chamar('POST', '/api/protocolos/alertas', $base + ['canais' => ['sms']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('canais.0');
    }

    public function test_presenca_do_chat_valida_estado_e_connection_id(): void
    {
        $this->chamar('POST', '/api/chat/presence', ['state' => 'invalido', 'connection_id' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['state', 'connection_id']);
    }
}
