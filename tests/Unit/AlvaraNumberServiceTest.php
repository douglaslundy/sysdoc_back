<?php

namespace Tests\Unit;

use App\Models\Alvara;
use App\Models\Estabelecimento;
use App\Services\AlvaraNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlvaraNumberServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_formato_correto_primeiro_alvara(): void
    {
        $numero = AlvaraNumberService::gerar('2026-01-15');

        $this->assertSame('01-01/2026', $numero);
    }

    public function test_sequencial_incrementa_no_mesmo_mes(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();

        Alvara::create([
            'numero_alvara'      => '01-01/2026',
            'nivel_risco'        => '1',
            'estabelecimento_id' => $estabelecimento->id,
            'data_alvara'        => '2026-01-10',
        ]);

        $segundo = AlvaraNumberService::gerar('2026-01-20');
        $this->assertSame('02-01/2026', $segundo);

        Alvara::create([
            'numero_alvara'      => $segundo,
            'nivel_risco'        => '2',
            'estabelecimento_id' => $estabelecimento->id,
            'data_alvara'        => '2026-01-20',
        ]);

        $terceiro = AlvaraNumberService::gerar('2026-01-25');
        $this->assertSame('03-01/2026', $terceiro);
    }

    public function test_sequencial_reinicia_em_novo_mes(): void
    {
        $estabelecimento = Estabelecimento::factory()->create();

        Alvara::create([
            'numero_alvara'      => '05-01/2026',
            'nivel_risco'        => '1',
            'estabelecimento_id' => $estabelecimento->id,
            'data_alvara'        => '2026-01-31',
        ]);

        $primeiro_fevereiro = AlvaraNumberService::gerar('2026-02-01');
        $this->assertSame('01-02/2026', $primeiro_fevereiro);
    }

    public function test_zero_a_esquerda_no_sequencial(): void
    {
        $numero = AlvaraNumberService::gerar('2026-03-01');

        $this->assertStringStartsWith('01-', $numero);
        $this->assertMatchesRegularExpression('/^\d{2}-\d{2}\/\d{4}$/', $numero);
    }

    public function test_zero_a_esquerda_no_mes(): void
    {
        $numero = AlvaraNumberService::gerar('2026-05-01');

        $this->assertStringContainsString('-05/', $numero);
    }

    public function test_ano_com_4_digitos(): void
    {
        $numero = AlvaraNumberService::gerar('2026-12-31');

        $this->assertStringEndsWith('/2026', $numero);
    }
}
