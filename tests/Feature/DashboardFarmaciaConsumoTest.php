<?php

namespace Tests\Feature;

use App\Models\MedicineDailyStatus;
use App\Models\MedicineItem;
use App\Models\MedicineMonthlyAcquisition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFarmaciaConsumoTest extends TestCase
{
    use RefreshDatabase;

    private function criarMedicamento(string $ingrediente, string $concentracao): MedicineItem
    {
        return MedicineItem::create([
            'internal_code' => strtoupper(substr($ingrediente, 0, 3)).'-'.random_int(1000, 9999),
            'active_ingredient' => $ingrediente,
            'concentration' => $concentracao,
            'pharmaceutical_form' => 'Comprimido',
            'presentation' => 'Caixa',
            'unit_measure' => 'un',
            'active' => true,
        ]);
    }

    private function lancarStatus(MedicineItem $medicamento, \Carbon\Carbon $data, float $quantidade): void
    {
        MedicineDailyStatus::create([
            'medicine_item_id' => $medicamento->id,
            'reference_date' => $data->toDateString(),
            'availability_status' => $quantidade > 0 ? 'available' : 'unavailable',
            'available_quantity' => $quantidade,
        ]);
    }

    private function lancarAquisicao(MedicineItem $medicamento, string $mes, float $quantidade): void
    {
        MedicineMonthlyAcquisition::create([
            'medicine_item_id' => $medicamento->id,
            'reference_month' => $mes,
            'acquired_quantity' => $quantidade,
            'unit_measure' => 'un',
        ]);
    }

    public function test_calcula_consumo_medio_e_risco_de_falta_corretamente(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        // Medicamento 1: consumo constante de 90/mes (com aquisicao no meio pra provar que ela entra na conta),
        // estoque atual baixo -> deve entrar em risco_falta.
        $dipirona = $this->criarMedicamento('Dipirona', '500mg');
        $mes3 = now()->subMonths(3);
        $mes2 = now()->subMonths(2);
        $mes1 = now()->subMonths(1);

        $this->lancarStatus($dipirona, $mes3->copy()->startOfMonth(), 300);
        $this->lancarStatus($dipirona, $mes3->copy()->endOfMonth(), 210); // consumo = 300+0-210 = 90

        $this->lancarStatus($dipirona, $mes2->copy()->startOfMonth(), 210);
        $this->lancarAquisicao($dipirona, $mes2->format('Y-m'), 50);
        $this->lancarStatus($dipirona, $mes2->copy()->endOfMonth(), 170); // consumo = 210+50-170 = 90

        $this->lancarStatus($dipirona, $mes1->copy()->startOfMonth(), 170);
        $this->lancarStatus($dipirona, $mes1->copy()->endOfMonth(), 80); // consumo = 170+0-80 = 90

        $this->lancarStatus($dipirona, now(), 15); // estoque atual

        // Medicamento 2: sem nenhum lancamento nos 3 meses fechados -> nao deve aparecer em nenhuma lista.
        $ibuprofeno = $this->criarMedicamento('Ibuprofeno', '600mg');
        $this->lancarStatus($ibuprofeno, now(), 100);

        // Medicamento 3: consumo baixo (20/mes), estoque alto -> aparece no ranking mas NAO em risco_falta.
        $paracetamol = $this->criarMedicamento('Paracetamol', '750mg');
        $this->lancarStatus($paracetamol, $mes3->copy()->startOfMonth(), 500);
        $this->lancarStatus($paracetamol, $mes3->copy()->endOfMonth(), 480);
        $this->lancarStatus($paracetamol, $mes2->copy()->startOfMonth(), 480);
        $this->lancarStatus($paracetamol, $mes2->copy()->endOfMonth(), 460);
        $this->lancarStatus($paracetamol, $mes1->copy()->startOfMonth(), 460);
        $this->lancarStatus($paracetamol, $mes1->copy()->endOfMonth(), 440);
        $this->lancarStatus($paracetamol, now(), 200);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/dashboard/farmacia');

        $response->assertOk();

        $ranking = collect($response->json('consumo_ranking'));
        $dipironaRanking = $ranking->firstWhere('medicine_item_id', $dipirona->id);
        $paracetamolRanking = $ranking->firstWhere('medicine_item_id', $paracetamol->id);
        $ibuprofenoRanking = $ranking->firstWhere('medicine_item_id', $ibuprofeno->id);

        $this->assertNotNull($dipironaRanking, 'Dipirona deveria aparecer no ranking de consumo');
        $this->assertEquals(90.0, $dipironaRanking['consumo_medio_mensal']);
        $this->assertEquals(3, $dipironaRanking['meses_com_dado']);
        $this->assertEquals('Dipirona 500mg', $dipironaRanking['nome']);

        $this->assertNotNull($paracetamolRanking, 'Paracetamol deveria aparecer no ranking de consumo');
        $this->assertEquals(20.0, $paracetamolRanking['consumo_medio_mensal']);

        $this->assertNull($ibuprofenoRanking, 'Ibuprofeno sem dado nos 3 meses fechados nao deveria aparecer no ranking');

        // Dipirona: consumo_medio_diario = 90/30 = 3.0; estoque_atual = 15; dias_restantes = 15/3 = 5.0
        $risco = collect($response->json('risco_falta'));
        $dipironaRisco = $risco->firstWhere('medicine_item_id', $dipirona->id);
        $paracetamolRisco = $risco->firstWhere('medicine_item_id', $paracetamol->id);

        $this->assertNotNull($dipironaRisco, 'Dipirona com 5 dias restantes deveria entrar em risco_falta');
        $this->assertEquals(3.0, $dipironaRisco['consumo_medio_diario']);
        $this->assertEquals(15.0, $dipironaRisco['estoque_atual']);
        $this->assertEquals(5.0, $dipironaRisco['dias_restantes']);

        $this->assertNull($paracetamolRisco, 'Paracetamol com dias_restantes >= 15 nao deveria entrar em risco_falta');
    }
}
