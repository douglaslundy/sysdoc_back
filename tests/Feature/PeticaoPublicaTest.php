<?php

namespace Tests\Feature;

use App\Models\Fiscalizacao;
use App\Models\PeticaoMotivo;
use App\Models\User;
use App\Models\VigilanciaContatoWhatsapp;
use App\Services\WhatsappEvolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PeticaoPublicaTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $mensagens = [];

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('private');
        $this->mensagens = [];

        $test = $this;
        $this->app->instance(WhatsappEvolutionService::class, new class($test) extends WhatsappEvolutionService {
            public function __construct(private $test)
            {
            }

            public function sendTextToNumber(string $number, string $message): array
            {
                $this->test->registrar($message);

                return ['ok' => true];
            }
        });
    }

    public function registrar(string $mensagem): void
    {
        $this->mensagens[] = $mensagem;
    }

    private function dados(array $extra = []): array
    {
        return $extra + [
            'assunto' => 'Falta de higiene', 'descricao_denuncia' => 'Alimentos expostos.',
            'local_endereco' => 'Rua das Flores, 100',
        ];
    }

    public function test_petition_grava_o_motivo_escolhido(): void
    {
        $motivo = PeticaoMotivo::create(['nome' => 'Solicitar vistoria']);

        $resposta = $this->postJson('/api/public/petitions', $this->dados(['motivo_id' => $motivo->id]))->assertCreated();

        $f = Fiscalizacao::where('protocolo', $resposta->json('protocolo'))->firstOrFail();
        $this->assertSame($motivo->id, $f->motivo_id);
        $this->assertSame('peticao', $f->origem);
    }

    public function test_com_motivos_ativos_o_motivo_e_obrigatorio_e_inativo_e_recusado(): void
    {
        $ativo = PeticaoMotivo::create(['nome' => 'Denúncia']);
        $inativo = PeticaoMotivo::create(['nome' => 'Antigo', 'ativo' => false]);

        $this->postJson('/api/public/petitions', $this->dados())->assertStatus(422)->assertJsonValidationErrors('motivo_id');
        $this->postJson('/api/public/petitions', $this->dados(['motivo_id' => $inativo->id]))->assertStatus(422);
        $this->postJson('/api/public/petitions', $this->dados(['motivo_id' => 99999]))->assertStatus(422);
        $this->postJson('/api/public/petitions', $this->dados(['motivo_id' => $ativo->id]))->assertCreated();
    }

    public function test_sem_nenhum_motivo_ativo_ainda_aceita_a_petition(): void
    {
        $this->postJson('/api/public/petitions', $this->dados())->assertCreated();
    }

    public function test_rota_antiga_de_denuncias_continua_funcionando(): void
    {
        $this->postJson('/api/public/denuncias', $this->dados())->assertCreated();
    }

    public function test_recibo_aponta_para_a_nova_pagina_de_acompanhamento(): void
    {
        config(['app.frontend_url' => 'https://sistema.exemplo.gov.br']);

        $resposta = $this->postJson('/api/public/petitions', $this->dados())->assertCreated();

        $this->assertSame(
            'https://sistema.exemplo.gov.br/petition/track?protocolo='.$resposta->json('protocolo'),
            $resposta->json('url_consulta')
        );
    }

    public function test_consulta_nova_rota_devolve_o_motivo(): void
    {
        $motivo = PeticaoMotivo::create(['nome' => 'Solicitar vistoria']);
        $criada = $this->postJson('/api/public/petitions', $this->dados(['motivo_id' => $motivo->id]))->assertCreated();

        $this->postJson('/api/public/petitions/consulta', [
            'protocolo' => $criada->json('protocolo'), 'senha' => $criada->json('senha'),
        ])->assertOk()->assertJsonPath('motivo', 'Solicitar vistoria');
    }

    public function test_aviso_de_whatsapp_informa_o_motivo(): void
    {
        VigilanciaContatoWhatsapp::create(['nome' => 'Ana', 'telefone' => '35998765432', 'ativo' => true]);
        $motivo = PeticaoMotivo::create(['nome' => 'Solicitar vistoria']);

        $this->postJson('/api/public/petitions', $this->dados(['motivo_id' => $motivo->id]))->assertCreated();

        $this->assertCount(1, $this->mensagens);
        $this->assertStringContainsString('Motivo: Solicitar vistoria', $this->mensagens[0]);
    }

    public function test_excluir_o_motivo_mantem_a_petition(): void
    {
        $motivo = PeticaoMotivo::create(['nome' => 'Temporário']);
        $protocolo = $this->postJson('/api/public/petitions', $this->dados(['motivo_id' => $motivo->id]))->json('protocolo');

        $motivo->delete();

        $this->assertNull(Fiscalizacao::where('protocolo', $protocolo)->firstOrFail()->motivo_id);
    }
}
