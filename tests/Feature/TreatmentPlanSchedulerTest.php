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
}
