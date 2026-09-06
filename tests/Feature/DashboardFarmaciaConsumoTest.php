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

        $this->travelTo(\Carbon\Carbon::parse('2026-06-15 12:00:00'));

        // Medicamento 1: consumo constante de 90/mes (com aquisicao no meio pra provar que ela entra na conta),
        // estoque atual baixo -> deve entrar em risco_falta.
        $dipirona = $this->criarMedicamento('Dipirona', '500mg');
        $mesInicial = now()->startOfMonth();
        $mes3 = $mesInicial->copy()->subMonths(3);
        $mes2 = $mesInicial->copy()->subMonths(2);
        $mes1 = $mesInicial->copy()->subMonths(1);

        $this->lancarStatus($dipirona, $mes3->copy()->startOfMonth(), 300);
        $this->lancarStatus($dipirona, $mes3->copy()->endOfMonth(), 210); // consumo = 300+0-210 = 90

        $this->lancarStatus($dipirona, $mes2->copy()->startOfMonth(), 210);
        $this->lancarAquisicao($dipirona, $mes2->format('Y-m'), 50);
        $this->lancarStatus($dipirona, $mes2->copy()->endOfMonth(), 170); // consumo = 210+50-170 = 90

        $this->lancarStatus($dipirona, $mes1->copy()->startOfMonth(), 170);
        $this->lancarStatus($dipirona, $mes1->copy()->endOfMonth(), 80); // consumo = 170+0-80 = 90

        // Grande alteracao no mes corrente (ainda aberto): nao deve entrar na media de consumo,
        // provando que o mes aberto e corretamente excluido do calculo.
        $this->lancarStatus($dipirona, $mesInicial->copy()->addDays(2), 9999);

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

        // Medicamento 4: consumo alto (120/mes), estoque baixo -> entra em risco_falta com dias_restantes entre Dipirona e nenhum outro
        $amoxicilina = $this->criarMedicamento('Amoxicilina', '500mg');
        $this->lancarStatus($amoxicilina, $mes3->copy()->startOfMonth(), 400);
        $this->lancarStatus($amoxicilina, $mes3->copy()->endOfMonth(), 280); // consumo = 400-280 = 120
        $this->lancarStatus($amoxicilina, $mes2->copy()->startOfMonth(), 280);
        $this->lancarStatus($amoxicilina, $mes2->copy()->endOfMonth(), 160); // consumo = 280-160 = 120
        $this->lancarStatus($amoxicilina, $mes1->copy()->startOfMonth(), 160);
        $this->lancarStatus($amoxicilina, $mes1->copy()->endOfMonth(), 40); // consumo = 160-40 = 120
        $this->lancarStatus($amoxicilina, now(), 40); // estoque atual: consumo_medio_diario = 120/30 = 4.0; dias_restantes = 40/4.0 = 10.0

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/dashboard/farmacia');

        $response->assertOk();

        $ranking = collect($response->json('consumo_ranking'));
        $dipironaRanking = $ranking->firstWhere('medicine_item_id', $dipirona->id);
        $paracetamolRanking = $ranking->firstWhere('medicine_item_id', $paracetamol->id);
        $ibuprofenoRanking = $ranking->firstWhere('medicine_item_id', $ibuprofeno->id);
        $amoxicilinRanking = $ranking->firstWhere('medicine_item_id', $amoxicilina->id);

        $this->assertNotNull($dipironaRanking, 'Dipirona deveria aparecer no ranking de consumo');
        $this->assertEquals(90.0, $dipironaRanking['consumo_medio_mensal']);
        $this->assertEquals(3, $dipironaRanking['meses_com_dado']);
        $this->assertEquals('Dipirona 500mg', $dipironaRanking['nome']);

        $this->assertNotNull($paracetamolRanking, 'Paracetamol deveria aparecer no ranking de consumo');
        $this->assertEquals(20.0, $paracetamolRanking['consumo_medio_mensal']);

        $this->assertNull($ibuprofenoRanking, 'Ibuprofeno sem dado nos 3 meses fechados nao deveria aparecer no ranking');

        $this->assertNotNull($amoxicilinRanking, 'Amoxicilina deveria aparecer no ranking de consumo');
        $this->assertEquals(120.0, $amoxicilinRanking['consumo_medio_mensal']);

        // Verify consumo_ranking is sorted descending by consumo_medio_mensal
        $ranking_array = $response->json('consumo_ranking');
        $this->assertGreaterThanOrEqual(120.0, $ranking_array[0]['consumo_medio_mensal'], 'Primeiro item deve ter maior ou igual consumo');
        $this->assertEquals($amoxicilinRanking['medicine_item_id'], $ranking_array[0]['medicine_item_id'], 'Amoxicilina (120/mes) deve ser primeira no ranking');
        $this->assertEquals($dipironaRanking['medicine_item_id'], $ranking_array[1]['medicine_item_id'], 'Dipirona (90/mes) deve ser segunda no ranking');
        $this->assertEquals($paracetamolRanking['medicine_item_id'], $ranking_array[2]['medicine_item_id'], 'Paracetamol (20/mes) deve ser terceira no ranking');

        // Dipirona: consumo_medio_diario = 90/30 = 3.0; estoque_atual = 15; dias_restantes = 15/3 = 5.0
        // Amoxicilina: consumo_medio_diario = 120/30 = 4.0; estoque_atual = 40; dias_restantes = 40/4.0 = 10.0
        $risco = collect($response->json('risco_falta'));
        $dipironaRisco = $risco->firstWhere('medicine_item_id', $dipirona->id);
        $paracetamolRisco = $risco->firstWhere('medicine_item_id', $paracetamol->id);
        $amoxicilinRisco = $risco->firstWhere('medicine_item_id', $amoxicilina->id);

        $this->assertNotNull($dipironaRisco, 'Dipirona com 5 dias restantes deveria entrar em risco_falta');
        $this->assertEquals(3.0, $dipironaRisco['consumo_medio_diario']);
        $this->assertEquals(15.0, $dipironaRisco['estoque_atual']);
        $this->assertEquals(5.0, $dipironaRisco['dias_restantes']);

        $this->assertNull($paracetamolRisco, 'Paracetamol com dias_restantes >= 15 nao deveria entrar em risco_falta');

        $this->assertNotNull($amoxicilinRisco, 'Amoxicilina com 10 dias restantes deveria entrar em risco_falta');
        $this->assertEquals(4.0, $amoxicilinRisco['consumo_medio_diario']);
        $this->assertEquals(40.0, $amoxicilinRisco['estoque_atual']);
        $this->assertEquals(10.0, $amoxicilinRisco['dias_restantes']);

        // Verify risco_falta is sorted ascending by dias_restantes
        $risco_array = $response->json('risco_falta');
        $this->assertCount(2, $risco_array, 'Deve haver exatamente 2 itens em risco_falta');
        $this->assertEquals($dipironaRisco['medicine_item_id'], $risco_array[0]['medicine_item_id'], 'Dipirona (5 dias) deve ser primeiro em risco_falta');
        $this->assertEquals($amoxicilinRisco['medicine_item_id'], $risco_array[1]['medicine_item_id'], 'Amoxicilina (10 dias) deve ser segundo em risco_falta');
    }

    public function test_consumo_ranking_trunca_para_top_10(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $this->travelTo(\Carbon\Carbon::parse('2026-06-15 12:00:00'));

        $mesInicial = now()->startOfMonth();
        $mes3 = $mesInicial->copy()->subMonths(3);
        $mes2 = $mesInicial->copy()->subMonths(2);
        $mes1 = $mesInicial->copy()->subMonths(1);

        // Create 12 medications with different consumption rates (110, 120, 130, ..., 220)
        $medicamentos = [];
        for ($i = 0; $i < 12; $i++) {
            $consumo = 110 + ($i * 10); // 110, 120, 130, ..., 220
            $medicamento = $this->criarMedicamento("Medicamento {$i}", "{$consumo}mg");
            $medicamentos[$i] = ['obj' => $medicamento, 'consumo' => $consumo];

            // Create 3 months of data for each medication
            $estoque_inicio = $consumo * 3 + 50; // start with 3 months of stock plus buffer
            $this->lancarStatus($medicamento, $mes3->copy()->startOfMonth(), $estoque_inicio);
            $estoque_m3_fim = $estoque_inicio - $consumo;
            $this->lancarStatus($medicamento, $mes3->copy()->endOfMonth(), $estoque_m3_fim);

            $this->lancarStatus($medicamento, $mes2->copy()->startOfMonth(), $estoque_m3_fim);
            $estoque_m2_fim = $estoque_m3_fim - $consumo;
            $this->lancarStatus($medicamento, $mes2->copy()->endOfMonth(), $estoque_m2_fim);

            $this->lancarStatus($medicamento, $mes1->copy()->startOfMonth(), $estoque_m2_fim);
            $estoque_m1_fim = $estoque_m2_fim - $consumo;
            $this->lancarStatus($medicamento, $mes1->copy()->endOfMonth(), $estoque_m1_fim);

            $this->lancarStatus($medicamento, now(), $estoque_m1_fim);
        }

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/dashboard/farmacia');

        $response->assertOk();

        $ranking = $response->json('consumo_ranking');

        // Assert exactly 10 items returned (not all 12)
        $this->assertCount(10, $ranking, 'consumo_ranking deve conter exatamente 10 itens (top 10 truncado)');

        // Assert they are the top 10 by consumo_medio_mensal in descending order
        $consumos_esperados = [220, 210, 200, 190, 180, 170, 160, 150, 140, 130];
        for ($i = 0; $i < 10; $i++) {
            $this->assertEquals(
                $consumos_esperados[$i],
                $ranking[$i]['consumo_medio_mensal'],
                "Posicao {$i} deveria ter consumo de {$consumos_esperados[$i]}"
            );
        }

        // Assert medicamentos com 120 e 110 (menores consumos) NAO aparecem
        $consumos_presentes = array_map(fn ($r) => $r['consumo_medio_mensal'], $ranking);
        $this->assertNotContains(120, $consumos_presentes, 'Medicamento com consumo 120 nao deveria aparecer');
        $this->assertNotContains(110, $consumos_presentes, 'Medicamento com consumo 110 nao deveria aparecer');
    }
}
