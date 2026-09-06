<?php

namespace Tests\Feature;

use App\Services\TreatmentPlanScheduler;
use Carbon\Carbon;
use Tests\TestCase;

class TreatmentPlanSchedulerTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_distribui_datas_nos_dias_da_semana_escolhidos(): void
    {
        // 2026-09-07 é uma segunda-feira.
        Carbon::setTestNow(Carbon::parse('2026-09-07'));

        $scheduler = new TreatmentPlanScheduler();
        $dates = $scheduler->distributeDates([1, 5], 4);

        $this->assertCount(4, $dates);
        $this->assertSame('2026-09-07', $dates[0]->toDateString()); // segunda (hoje)
        $this->assertSame('2026-09-11', $dates[1]->toDateString()); // sexta
        $this->assertSame('2026-09-14', $dates[2]->toDateString()); // segunda seguinte
        $this->assertSame('2026-09-18', $dates[3]->toDateString()); // sexta seguinte
    }

    public function test_comeca_no_proximo_dia_da_semana_escolhido_se_hoje_nao_for_um_deles(): void
    {
        // 2026-09-08 é uma terça-feira; especialidade só atende quarta (3).
        Carbon::setTestNow(Carbon::parse('2026-09-08'));

        $scheduler = new TreatmentPlanScheduler();
        $dates = $scheduler->distributeDates([3], 2);

        $this->assertSame('2026-09-09', $dates[0]->toDateString());
        $this->assertSame('2026-09-16', $dates[1]->toDateString());
    }

    public function test_aceita_um_ponto_de_partida_explicito(): void
    {
        $scheduler = new TreatmentPlanScheduler();
        $dates = $scheduler->distributeDates([1], 1, Carbon::parse('2026-09-14'));

        $this->assertSame('2026-09-14', $dates[0]->toDateString());
    }

    public function test_aceita_dias_da_semana_como_string_sem_travar(): void
    {
        // Regressão: Request::validate() com a regra "integer" não faz cast — valores
        // numéricos em formato string (ex.: vindos de um POST form-encoded) passam na
        // validação mas quebravam o antigo in_array(..., true) estrito.
        Carbon::setTestNow(Carbon::parse('2026-09-07'));

        $scheduler = new TreatmentPlanScheduler();
        $dates = $scheduler->distributeDates(['1', '5'], 4);

        $this->assertCount(4, $dates);
        $this->assertSame('2026-09-07', $dates[0]->toDateString());
        $this->assertSame('2026-09-11', $dates[1]->toDateString());
        $this->assertSame('2026-09-14', $dates[2]->toDateString());
        $this->assertSame('2026-09-18', $dates[3]->toDateString());
    }

    public function test_lanca_excecao_em_vez_de_travar_quando_nenhum_dia_e_valido(): void
    {
        // Sem guarda, in_array() nunca teria um dayOfWeekIso de 1 a 7 correspondente
        // a um array vazio, e o while(...) rodaria para sempre.
        $this->expectException(\InvalidArgumentException::class);

        $scheduler = new TreatmentPlanScheduler();
        $scheduler->distributeDates([], 3);
    }
}
