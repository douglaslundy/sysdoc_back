<?php

namespace Tests\Feature;

use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\User;
use App\Models\VigilanciaContatoWhatsapp;
use App\Services\WhatsappEvolutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VigilanciaAvisoWhatsappTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, array{0: string, 1: string}> */
    private array $enviadas = [];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('private');
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->enviadas = [];

        $test = $this;
        $this->app->instance(WhatsappEvolutionService::class, new class($test) extends WhatsappEvolutionService {
            public function __construct(private $test)
            {
            }

            public function sendTextToNumber(string $number, string $message): array
            {
                $this->test->registrarEnvio($number, $message);

                return ['ok' => true];
            }
        });
    }

    public function registrarEnvio(string $numero, string $mensagem): void
    {
        $this->enviadas[] = [$numero, $mensagem];
    }

    private function denuncia(array $extra = [])
    {
        return $this->postJson('/api/public/denuncias', $extra + [
            'assunto' => 'Falta de higiene', 'descricao_denuncia' => 'Alimentos expostos.',
            'local_endereco' => 'Rua das Flores, 100', 'denunciante_nome' => 'Maria Sigilosa', 'denunciante_contato' => '35 99999-0000',
        ]);
    }

    // ---- cadastro (config da Vigilância) ----

    public function test_admin_cadastra_lista_edita_e_remove_contatos(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $id = $this->postJson('/api/vigilancia/contatos-whatsapp', ['nome' => 'Ana Fiscal', 'telefone' => '(35) 99876-5432'])
            ->assertCreated()->assertJsonPath('telefone', '35998765432')->assertJsonPath('ativo', true)->json('id');

        $this->getJson('/api/vigilancia/contatos-whatsapp')->assertOk()->assertJsonCount(1)->assertJsonPath('0.nome', 'Ana Fiscal');

        $this->putJson("/api/vigilancia/contatos-whatsapp/{$id}", ['nome' => 'Ana F.', 'telefone' => '35998765432', 'ativo' => false])
            ->assertOk()->assertJsonPath('ativo', false);

        $this->deleteJson("/api/vigilancia/contatos-whatsapp/{$id}")->assertOk();
        $this->assertSame(0, VigilanciaContatoWhatsapp::count());
    }

    public function test_valida_nome_e_telefone(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $this->postJson('/api/vigilancia/contatos-whatsapp', ['nome' => '', 'telefone' => '123'])
            ->assertStatus(422)->assertJsonValidationErrors(['nome', 'telefone']);
    }

    public function test_so_admin_gerencia_os_contatos(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        $this->actingAs($user, 'sanctum')->getJson('/api/vigilancia/contatos-whatsapp')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->actingAs($user, 'sanctum')->postJson('/api/vigilancia/contatos-whatsapp', ['nome' => 'X', 'telefone' => '35998765432'])->assertForbidden();
    }

    // ---- aviso ----

    public function test_denuncia_nova_avisa_so_os_contatos_ativos_sem_expor_o_denunciante(): void
    {
        VigilanciaContatoWhatsapp::create(['nome' => 'Ana', 'telefone' => '35998765432', 'ativo' => true]);
        VigilanciaContatoWhatsapp::create(['nome' => 'Bia', 'telefone' => '35991112222', 'ativo' => true]);
        VigilanciaContatoWhatsapp::create(['nome' => 'Inativa', 'telefone' => '35990000000', 'ativo' => false]);

        $protocolo = $this->denuncia()->assertCreated()->json('protocolo');

        $this->assertCount(2, $this->enviadas);
        $numeros = array_column($this->enviadas, 0);
        $this->assertEqualsCanonicalizing(['35998765432', '35991112222'], $numeros);

        $mensagem = $this->enviadas[0][1];
        $this->assertStringContainsString($protocolo, $mensagem);
        $this->assertStringContainsString('Falta de higiene', $mensagem);
        $this->assertStringContainsString('Rua das Flores, 100', $mensagem);
        $this->assertStringNotContainsString('Maria Sigilosa', $mensagem);
        $this->assertStringNotContainsString('99999-0000', $mensagem);
        $this->assertStringNotContainsString('Alimentos expostos', $mensagem);
    }

    public function test_aviso_fica_registrado_no_historico_interno_e_nao_no_publico(): void
    {
        VigilanciaContatoWhatsapp::create(['nome' => 'Ana', 'telefone' => '35998765432', 'ativo' => true]);

        $protocolo = $this->denuncia()->assertCreated()->json('protocolo');
        $f = Fiscalizacao::where('protocolo', $protocolo)->firstOrFail();

        $aviso = $f->movimentacoes()->where('acao', 'aviso_whatsapp')->first();
        $this->assertNotNull($aviso);
        $this->assertFalse($aviso->publico);
        $this->assertSame(1, $aviso->dados['enviados']);
        $this->assertSame(0, $aviso->dados['falhas']);
    }

    public function test_fiscalizacao_interna_criada_tambem_avisa(): void
    {
        VigilanciaContatoWhatsapp::create(['nome' => 'Ana', 'telefone' => '35998765432', 'ativo' => true]);
        $estabelecimento = Estabelecimento::factory()->create(['nome_estabelecimento' => 'Padaria Central']);

        $protocolo = $this->actingAs($this->admin, 'sanctum')->postJson('/api/fiscalizacoes', [
            'estabelecimento_id' => $estabelecimento->id, 'data_visita' => '2026-09-30', 'resultado' => 'Notificação',
        ])->assertCreated()->json('protocolo');

        $this->assertCount(1, $this->enviadas);
        $this->assertStringContainsString($protocolo, $this->enviadas[0][1]);
        $this->assertStringContainsString('Padaria Central', $this->enviadas[0][1]);
    }

    public function test_sem_contatos_nada_e_enviado_e_nada_quebra(): void
    {
        $this->denuncia()->assertCreated();

        $this->assertSame([], $this->enviadas);
        $this->assertSame(0, \App\Models\FiscalizacaoMovimentacao::where('acao', 'aviso_whatsapp')->count());
    }

    public function test_falha_no_envio_nao_impede_o_registro_da_denuncia_e_conta_como_falha(): void
    {
        VigilanciaContatoWhatsapp::create(['nome' => 'Ana', 'telefone' => '35998765432', 'ativo' => true]);
        $this->app->instance(WhatsappEvolutionService::class, new class extends WhatsappEvolutionService {
            public function __construct()
            {
            }

            public function sendTextToNumber(string $number, string $message): array
            {
                throw new \RuntimeException('Evolution fora do ar');
            }
        });

        $protocolo = $this->denuncia()->assertCreated()->json('protocolo');

        $f = Fiscalizacao::where('protocolo', $protocolo)->firstOrFail();
        $this->assertSame(1, $f->movimentacoes()->where('acao', 'aviso_whatsapp')->first()->dados['falhas']);
    }

    public function test_isca_preenchida_nao_dispara_aviso(): void
    {
        VigilanciaContatoWhatsapp::create(['nome' => 'Ana', 'telefone' => '35998765432', 'ativo' => true]);

        $this->denuncia(['website' => 'http://spam'])->assertCreated();

        $this->assertSame([], $this->enviadas);
    }
}
