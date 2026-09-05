<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\Alvara;
use App\Models\Estabelecimento;
use App\Models\MedicineDailyStatus;
use App\Models\MedicineItem;
use App\Models\Protocol;
use App\Models\SystemPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardInicioTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_recebe_os_7_setores_com_valores_corretos(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        // Farmácia: 1 medicamento indisponível hoje, 1 disponível.
        $indisponivel = MedicineItem::create([
            'internal_code' => 'MED-001', 'active_ingredient' => 'Dipirona',
            'concentration' => '500mg', 'pharmaceutical_form' => 'Comprimido',
            'presentation' => 'Caixa', 'unit_measure' => 'un', 'active' => true,
        ]);
        MedicineDailyStatus::create([
            'medicine_item_id' => $indisponivel->id, 'reference_date' => now()->toDateString(),
            'availability_status' => 'unavailable', 'available_quantity' => 0,
        ]);
        $disponivel = MedicineItem::create([
            'internal_code' => 'MED-002', 'active_ingredient' => 'Paracetamol',
            'concentration' => '750mg', 'pharmaceutical_form' => 'Comprimido',
            'presentation' => 'Caixa', 'unit_measure' => 'un', 'active' => true,
        ]);
        MedicineDailyStatus::create([
            'medicine_item_id' => $disponivel->id, 'reference_date' => now()->toDateString(),
            'availability_status' => 'available', 'available_quantity' => 50,
        ]);

        // Vigilância: 1 alvará vencido, 1 vigente.
        $estabelecimento = Estabelecimento::create([
            'nome_estabelecimento' => 'Farmácia Central',
            'nome_responsavel' => 'Responsável Teste',
            'endereco' => 'Rua Teste, 123',
            'cnaes' => 'farmacia',
        ]);
        Alvara::create([
            'numero_alvara' => 'ALV-001', 'status' => 'Deferido',
            'estabelecimento_id' => $estabelecimento->id, 'nivel_risco' => 'alto',
            'data_alvara' => now()->subMonths(1)->toDateString(),
            'vencimento_alvara' => now()->subDay()->toDateString(),
        ]);
        Alvara::create([
            'numero_alvara' => 'ALV-002', 'status' => 'Deferido',
            'estabelecimento_id' => $estabelecimento->id, 'nivel_risco' => 'baixo',
            'data_alvara' => now()->subMonths(1)->toDateString(),
            'vencimento_alvara' => now()->addMonths(6)->toDateString(),
        ]);

        // Almoxarifado: 1 produto abaixo do mínimo, 1 acima.
        $produtoBaixo = DB::table('almoxarifado_produtos')->insertGetId([
            'nome' => 'Papel A4', 'codigo_interno' => 'ALX-001',
            'estoque_minimo' => 100, 'ativo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('almoxarifado_estoques')->insert([
            'almoxarifado_produto_id' => $produtoBaixo, 'quantidade_disponivel' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $produtoOk = DB::table('almoxarifado_produtos')->insertGetId([
            'nome' => 'Caneta', 'codigo_interno' => 'ALX-002',
            'estoque_minimo' => 10, 'ativo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('almoxarifado_estoques')->insert([
            'almoxarifado_produto_id' => $produtoOk, 'quantidade_disponivel' => 200,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        // Protocolo: 1 vencido, 1 no prazo.
        Protocol::create([
            'numero' => 'PRT-001', 'assunto' => 'Vencido', 'tipo' => 'administrativo',
            'status' => 'novo', 'prioridade' => 'normal', 'solicitante_tipo' => 'interno',
            'criado_por_id' => $admin->id, 'novo' => true,
            'prazo_atendimento' => now()->subDay()->toDateString(),
        ]);
        Protocol::create([
            'numero' => 'PRT-002', 'assunto' => 'No prazo', 'tipo' => 'administrativo',
            'status' => 'novo', 'prioridade' => 'normal', 'solicitante_tipo' => 'interno',
            'criado_por_id' => $admin->id, 'novo' => true,
            'prazo_atendimento' => now()->addDays(5)->toDateString(),
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/dashboard/inicio');

        $response->assertOk();
        $response->assertJsonPath('setores.farmacia.valor', 1);
        $response->assertJsonPath('setores.farmacia.alerta', true);
        $response->assertJsonPath('setores.vigilancia.valor', 1);
        $response->assertJsonPath('setores.vigilancia.alerta', true);
        $response->assertJsonPath('setores.almoxarifado.valor', 1);
        $response->assertJsonPath('setores.almoxarifado.alerta', true);
        $response->assertJsonPath('setores.protocolo.valor', 1);
        $response->assertJsonPath('setores.protocolo.alerta', true);
        $response->assertJsonPath('setores.laboratorio.alerta', false);
        $response->assertJsonPath('setores.fila.alerta', false);
        $response->assertJsonPath('setores.tfd.alerta', false);
        $response->assertJsonStructure([
            'setores' => [
                'farmacia' => ['label', 'valor', 'alerta'],
                'vigilancia' => ['label', 'valor', 'alerta'],
                'almoxarifado' => ['label', 'valor', 'alerta'],
                'protocolo' => ['label', 'valor', 'alerta'],
                'laboratorio' => ['label', 'valor', 'alerta'],
                'fila' => ['label', 'valor', 'alerta'],
                'tfd' => ['label', 'valor', 'alerta'],
            ],
        ]);
    }

    public function test_setores_sem_dado_ficam_zerados_e_sem_alerta(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/dashboard/inicio');

        $response->assertOk();
        $response->assertJsonPath('setores.farmacia.valor', 0);
        $response->assertJsonPath('setores.farmacia.alerta', false);
        $response->assertJsonPath('setores.vigilancia.valor', 0);
        $response->assertJsonPath('setores.almoxarifado.valor', 0);
        $response->assertJsonPath('setores.protocolo.valor', 0);
    }

    public function test_usuario_sem_permissao_de_pagina_nao_recebe_o_setor(): void
    {
        $profile = AccessProfile::create([
            'nome' => 'Perfil Restrito', 'slug' => 'rest'.rand(100, 999),
            'descricao' => 'Perfil de teste', 'ativo' => true,
        ]);
        foreach (['/dashboard/inicio', '/dashboard/farmacia', '/dashboard/laboratorio'] as $path) {
            $page = SystemPage::create(['titulo' => 'Pagina '.$path, 'path' => $path, 'ativo' => true]);
            $profile->pages()->attach($page->id);
        }
        $user = User::factory()->create(['profile' => $profile->slug, 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/dashboard/inicio');

        $response->assertOk();
        $response->assertJsonMissingPath('setores.vigilancia');
        $response->assertJsonMissingPath('setores.almoxarifado');
        $response->assertJsonMissingPath('setores.protocolo');
        $response->assertJsonMissingPath('setores.fila');
        $response->assertJsonMissingPath('setores.tfd');
        $response->assertJsonStructure(['setores' => ['farmacia', 'laboratorio']]);
    }
}
