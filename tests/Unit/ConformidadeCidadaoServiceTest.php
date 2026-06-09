<?php

namespace Tests\Unit;

use App\Models\Addresses;
use App\Models\Client;
use App\Services\ConformidadeCidadaoService;
use ReflectionMethod;
use Tests\TestCase;

class ConformidadeCidadaoServiceTest extends TestCase
{
    public function test_diff_preenche_campos_vazios_sem_sobrescrever_divergentes_quando_esus_nao_e_mais_recente(): void
    {
        $client = new Client([
            'name' => 'Nome Sysdoc',
            'cpf' => '11111111111',
            'cns' => null,
            'born_date' => '1990-01-01',
            'phone' => '35999990000',
            'sexo' => 'MASCULINE',
        ]);

        $client->setRelation('addresses', new Addresses([
            'street' => 'Rua Sysdoc',
            'number' => '',
            'zip_code' => null,
            'district' => 'Centro',
            'city' => 'Ilicinea',
        ]));

        $row = [
            'nome' => 'Nome ESUS',
            'mae' => null,
            'cpf' => '22222222222',
            'cns' => '123456789012345',
            'dt_nasc' => '1991-02-02',
            'sexo' => 'FEMININE',
            'telefone' => '35888880000',
            'logradouro' => 'Rua ESUS',
            'numero' => '123',
            'cep' => '37175000',
            'bairro' => 'Bairro ESUS',
            'complemento' => null,
            'municipio' => 'Ilicinea',
            'raca_cor' => null,
            'escolaridade' => null,
            'nacionalidade' => null,
        ];

        $diff = $this->buildDiff($client, $row, false);

        $this->assertArrayNotHasKey('nome', $diff);
        $this->assertArrayNotHasKey('cpf', $diff);
        $this->assertArrayNotHasKey('born_date', $diff);
        $this->assertArrayNotHasKey('sexo', $diff);
        $this->assertArrayNotHasKey('phone', $diff);
        $this->assertSame('123456789012345', $diff['cns']['para']);
        $this->assertArrayNotHasKey('street', $diff['address']);
        $this->assertSame('123', $diff['address']['number']['para']);
        $this->assertSame('37175000', $diff['address']['zip_code']['para']);
        $this->assertArrayNotHasKey('district', $diff['address']);
    }

    public function test_diff_sobrescreve_divergentes_quando_esus_e_mais_recente(): void
    {
        $client = new Client([
            'name' => 'Nome Sysdoc',
            'cpf' => '11111111111',
            'born_date' => '1990-01-01',
        ]);

        $client->setRelation('addresses', new Addresses([
            'street' => 'Rua Sysdoc',
            'number' => '10',
            'zip_code' => '11111000',
            'district' => 'Centro',
            'city' => 'Ilicinea',
        ]));

        $row = [
            'nome' => 'Nome ESUS',
            'mae' => null,
            'cpf' => '22222222222',
            'cns' => null,
            'dt_nasc' => '1991-02-02',
            'sexo' => null,
            'telefone' => null,
            'logradouro' => 'Rua ESUS',
            'numero' => '123',
            'cep' => '37175000',
            'bairro' => 'Bairro ESUS',
            'complemento' => null,
            'municipio' => 'Ilicinea',
            'raca_cor' => null,
            'escolaridade' => null,
            'nacionalidade' => null,
        ];

        $diff = $this->buildDiff($client, $row, true);

        $this->assertSame('Nome ESUS', $diff['nome']['para']);
        $this->assertSame('22222222222', $diff['cpf']['para']);
        $this->assertSame('1991-02-02', $diff['born_date']['para']);
        $this->assertSame('Rua ESUS', $diff['address']['street']['para']);
        $this->assertSame('123', $diff['address']['number']['para']);
    }

    public function test_diff_preenche_data_nascimento_vazia_no_sysdoc(): void
    {
        $client = new Client([
            'name' => 'Nome Sysdoc',
            'cpf' => '11111111111',
            'born_date' => null,
        ]);

        $client->setRelation('addresses', null);

        $row = [
            'nome' => 'Nome Sysdoc',
            'mae' => null,
            'cpf' => '11111111111',
            'cns' => null,
            'dt_nasc' => '1985-03-04',
            'sexo' => null,
            'telefone' => null,
            'logradouro' => null,
            'numero' => null,
            'cep' => null,
            'bairro' => null,
            'complemento' => null,
            'municipio' => null,
            'raca_cor' => null,
            'escolaridade' => null,
            'nacionalidade' => null,
        ];

        $diff = $this->buildDiff($client, $row, false);

        $this->assertSame('1985-03-04', $diff['born_date']['para']);
    }

    public function test_is_obito_row_considera_data_de_obito_mesmo_sem_flag(): void
    {
        $service = new ConformidadeCidadaoService();
        $method = new ReflectionMethod($service, 'isObitoRow');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($service, [
            'st_faleceu' => null,
            'dt_obito' => '2026-01-01',
        ]));
    }

    public function test_should_process_obito_when_client_is_active_and_row_indicates_obito(): void
    {
        $service = new ConformidadeCidadaoService();
        $method = new ReflectionMethod($service, 'shouldProcessObito');
        $method->setAccessible(true);

        $client = new Client(['active' => true]);

        $this->assertTrue($method->invoke($service, $client, [
            'st_faleceu' => '1',
            'dt_obito' => null,
        ]));

        $client->active = false;

        $this->assertFalse($method->invoke($service, $client, [
            'st_faleceu' => '1',
            'dt_obito' => null,
        ]));
    }

    private function buildDiff(Client $client, array $row, bool $allowOverwrite): array
    {
        $service = new ConformidadeCidadaoService();
        $method = new ReflectionMethod($service, 'buildDiffPayload');
        $method->setAccessible(true);

        return $method->invoke($service, $client, $row, [], $allowOverwrite);
    }
}
