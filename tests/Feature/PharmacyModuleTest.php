<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\MedicineDailyStatus;
use App\Models\MedicineItem;
use App\Models\MedicineMonthlyAcquisition;
use App\Models\SystemPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyModuleTest extends TestCase
{
    use RefreshDatabase;

    private function adminUser(): User
    {
        return User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    private function userWithPermissions(array $paths): User
    {
        $slug = 'perfil-farmacia-'.uniqid();
        $profile = AccessProfile::create([
            'nome' => 'Perfil Farmácia '.uniqid(),
            'slug' => $slug,
            'descricao' => 'Perfil de teste',
            'ativo' => true,
        ]);

        foreach ($paths as $path) {
            $page = SystemPage::create([
                'titulo' => 'Página '.$path,
                'path' => $path,
                'ativo' => true,
            ]);
            $profile->pages()->attach($page->id);
        }

        return User::factory()->create(['profile' => $slug, 'active' => true]);
    }

    private function medicinePayload(array $overrides = []): array
    {
        return array_merge([
            'internal_code' => 'MED-'.rand(1000, 9999),
            'brand_name' => 'Marca Teste',
            'active_ingredient' => 'Dipirona',
            'concentration' => '500mg',
            'pharmaceutical_form' => 'Comprimido',
            'presentation' => 'Caixa com 20',
            'unit_measure' => 'cp',
            'ean_code' => '7891234567890',
            'is_free_distribution' => true,
            'is_controlled' => false,
            'active' => true,
            'technical_notes' => 'Observação técnica',
        ], $overrides);
    }

    public function test_usuario_sem_permissao_recebe_403_no_modulo_de_medicamentos(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/medicines');

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Você não possui permissão para executar esta ação.');
    }

    public function test_usuario_com_permissao_lista_medicamentos(): void
    {
        $user = $this->userWithPermissions(['/pharmacy/medicines']);
        MedicineItem::create($this->medicinePayload());

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/medicines');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'internal_code', 'active_ingredient']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    public function test_validacao_de_medicamento_retorna_mensagem_especifica(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/medicines', []);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'O código interno é obrigatório.')
            ->assertJsonValidationErrors(['internal_code', 'active_ingredient', 'concentration']);
    }

    public function test_crud_basico_de_medicamento_funciona(): void
    {
        $user = $this->adminUser();

        $create = $this->actingAs($user, 'sanctum')
            ->postJson('/api/medicines', $this->medicinePayload());
        $create->assertStatus(201);
        $id = $create->json('id');

        $update = $this->actingAs($user, 'sanctum')
            ->putJson("/api/medicines/{$id}", [
                'active_ingredient' => 'Paracetamol',
                'concentration' => '750mg',
                'pharmaceutical_form' => 'Comprimido',
                'presentation' => 'Caixa com 10',
                'unit_measure' => 'cp',
            ]);
        $update->assertStatus(200)
            ->assertJsonPath('active_ingredient', 'Paracetamol');

        $delete = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/medicines/{$id}");
        $delete->assertStatus(200)
            ->assertJsonPath('message', 'Medicamento removido com sucesso.');
    }

    public function test_daily_status_cria_e_remove_com_permissao(): void
    {
        $user = $this->adminUser();
        $medicine = MedicineItem::create($this->medicinePayload());

        $create = $this->actingAs($user, 'sanctum')
            ->postJson('/api/pharmacy/medicines/daily-statuses', [
                'medicine_item_id' => $medicine->id,
                'reference_date' => '2026-05-13',
                'availability_status' => 'available',
                'available_quantity' => 10,
            ]);
        $create->assertStatus(200);
        $id = $create->json('id');

        $delete = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/pharmacy/medicines/daily-statuses/{$id}");
        $delete->assertStatus(200)
            ->assertJsonPath('message', 'Status diário removido com sucesso.');
    }

    public function test_monthly_acquisition_valida_formato_do_mes(): void
    {
        $user = $this->adminUser();
        $medicine = MedicineItem::create($this->medicinePayload());

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/pharmacy/medicines/monthly-acquisitions', [
                'medicine_item_id' => $medicine->id,
                'reference_month' => '2026/05',
                'acquired_quantity' => 100,
                'unit_measure' => 'cx',
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'O mês de referência deve estar no formato AAAA-MM.')
            ->assertJsonValidationErrors(['reference_month']);
    }

    public function test_publicacao_retorna_404_para_referencia_inexistente(): void
    {
        $user = $this->adminUser();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/pharmacy/medicines/publications', [
                'reference_type' => 'daily',
                'reference_id' => 99999,
                'channel' => 'site',
                'status' => 'published',
            ]);

        $response->assertStatus(404)
            ->assertJsonPath('message', 'Referência de publicação não encontrada.');
    }

    public function test_publicacao_fluxo_completo_com_listagem_e_remocao(): void
    {
        $user = $this->adminUser();
        $medicine = MedicineItem::create($this->medicinePayload());
        $daily = MedicineDailyStatus::create([
            'medicine_item_id' => $medicine->id,
            'reference_date' => '2026-05-13',
            'availability_status' => 'available',
            'available_quantity' => 5,
            'updated_by_user_id' => $user->id,
        ]);

        $create = $this->actingAs($user, 'sanctum')
            ->postJson('/api/pharmacy/medicines/publications', [
                'reference_type' => 'daily',
                'reference_id' => $daily->id,
                'channel' => 'site',
                'status' => 'published',
            ]);
        $create->assertStatus(201)->assertJsonPath('reference_type', 'daily');
        $id = $create->json('id');

        $list = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pharmacy/medicines/publications?reference_type=daily');
        $list->assertStatus(200)
            ->assertJsonStructure([
                'data' => [['id', 'reference_type', 'reference_id', 'channel', 'status']],
                'meta' => ['current_page', 'last_page', 'total'],
            ]);

        $delete = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/pharmacy/medicines/publications/{$id}");
        $delete->assertStatus(200)
            ->assertJsonPath('message', 'Publicação removida com sucesso.');
    }

    public function test_endpoint_publico_mensal_valida_parametro_month(): void
    {
        $response = $this->getJson('/api/public/pharmacy/medicines/monthly-acquisitions?month=2026/13');

        $response->assertStatus(422)
            ->assertJsonPath('message', 'O parâmetro month deve estar no formato AAAA-MM.')
            ->assertJsonValidationErrors(['month']);
    }

    public function test_compliance_retorna_estrutura_padronizada(): void
    {
        $user = $this->adminUser();
        $medicine = MedicineItem::create($this->medicinePayload());

        MedicineDailyStatus::create([
            'medicine_item_id' => $medicine->id,
            'reference_date' => now()->toDateString(),
            'availability_status' => 'available',
            'available_quantity' => 7,
            'updated_by_user_id' => $user->id,
        ]);
        MedicineMonthlyAcquisition::create([
            'medicine_item_id' => $medicine->id,
            'reference_month' => now()->format('Y-m'),
            'acquired_quantity' => 12,
            'unit_measure' => 'cx',
            'updated_by_user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/pharmacy/medicines/compliance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'today_reference_date',
                'month_reference',
                'daily_updates_days_count',
                'daily_updates_expected_days_count',
                'monthly_acquisitions_count',
                'has_today_update',
            ]);
    }
}
