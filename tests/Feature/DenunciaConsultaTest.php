<?php

namespace Tests\Feature;

use App\Models\Fiscalizacao;
use App\Models\User;
use App\Services\Fiscalizacao\FiscalizacaoTimeline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DenunciaConsultaTest extends TestCase
{
    use RefreshDatabase;

    private const SENHA = 'K7Q29XMD';

    private Fiscalizacao $denuncia;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->denuncia = Fiscalizacao::create([
            'resultado' => 'Pendente de apuração', 'origem' => 'peticao', 'assunto' => 'Falta de higiene',
            'local_endereco' => 'Rua das Flores, 100', 'descricao_denuncia' => 'Descrição só do denunciante',
        ]);
        $this->denuncia->forceFill([
            'protocolo' => 'FIS-2026-000042', 'senha_consulta_hash' => Hash::make(self::SENHA),
        ])->saveQuietly();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function consultar(string $protocolo, string $senha, string $ip = '10.9.9.9')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/public/denuncias/consulta', ['protocolo' => $protocolo, 'senha' => $senha]);
    }

    public function test_senha_correta_mostra_apenas_o_que_e_publico_sem_nomes_de_fiscais(): void
    {
        $fiscal = User::factory()->create(['name' => 'Fiscal Secreto', 'profile' => 'admin', 'active' => true]);
        $timeline = app(FiscalizacaoTimeline::class);
        Carbon::setTestNow('2026-09-30 08:00:00');
        $timeline->registrar($this->denuncia, 'denuncia_recebida', 'Denúncia recebida', true, null);
        Carbon::setTestNow('2026-09-30 09:00:00');
        $timeline->registrar($this->denuncia, 'observacao', 'NOTA INTERNA CONFIDENCIAL', false, $fiscal->id);
        Carbon::setTestNow('2026-09-30 10:00:00');
        $timeline->registrar($this->denuncia, 'mensagem_publica', 'Vistoria marcada para segunda.', true, $fiscal->id);

        $response = $this->consultar('FIS-2026-000042', self::SENHA)->assertOk();

        $response->assertJsonPath('protocolo', 'FIS-2026-000042')
            ->assertJsonPath('situacao', 'Pendente de apuração')
            ->assertJsonPath('assunto', 'Falta de higiene')
            ->assertJsonPath('local_endereco', 'Rua das Flores, 100')
            ->assertJsonCount(2, 'movimentacoes')
            ->assertJsonPath('movimentacoes.0.titulo', 'Mensagem ao denunciante')
            ->assertJsonPath('movimentacoes.0.descricao', 'Vistoria marcada para segunda.')
            ->assertJsonPath('movimentacoes.1.titulo', 'Denúncia recebida');

        $json = $response->getContent();
        $this->assertStringNotContainsString('Fiscal Secreto', $json);
        $this->assertStringNotContainsString('NOTA INTERNA CONFIDENCIAL', $json);
        $this->assertStringNotContainsString('user_id', $json);
        $this->assertStringNotContainsString('senha_consulta_hash', $json);
    }

    public function test_situacao_apos_apuracao_nao_revela_o_resultado_interno(): void
    {
        $this->denuncia->update(['resultado' => 'Auto de infração']);

        $this->consultar('FIS-2026-000042', self::SENHA)->assertOk()->assertJsonPath('situacao', 'Apurada');
    }

    public function test_senha_errada_e_protocolo_inexistente_dao_exatamente_a_mesma_resposta(): void
    {
        $errada = $this->consultar('FIS-2026-000042', 'ZZZZZZZZ')->assertNotFound();
        $inexistente = $this->consultar('FIS-2026-999999', self::SENHA)->assertNotFound();

        $this->assertSame($errada->getContent(), $inexistente->getContent());
        $this->assertSame('Protocolo ou senha inválidos.', $errada->json('error'));
    }

    public function test_cinco_senhas_erradas_bloqueiam_por_quinze_minutos(): void
    {
        Carbon::setTestNow('2026-09-30 08:00:00');
        foreach (range(1, 5) as $ignored) {
            $this->consultar('FIS-2026-000042', 'ZZZZZZZZ', '10.9.9.'.random_int(10, 99))->assertNotFound();
        }

        // Mesmo com a senha certa (e de outro IP) continua bloqueado.
        $this->consultar('FIS-2026-000042', self::SENHA, '10.7.7.7')->assertStatus(429);

        Carbon::setTestNow('2026-09-30 08:16:00');
        $this->consultar('FIS-2026-000042', self::SENHA, '10.7.7.7')->assertOk();
    }

    public function test_acerto_zera_o_contador_de_falhas(): void
    {
        foreach (range(1, 4) as $ignored) {
            $this->consultar('FIS-2026-000042', 'ZZZZZZZZ', '10.9.9.'.random_int(10, 99))->assertNotFound();
        }
        $this->consultar('FIS-2026-000042', self::SENHA)->assertOk();

        foreach (range(1, 4) as $ignored) {
            $this->consultar('FIS-2026-000042', 'ZZZZZZZZ', '10.8.8.'.random_int(10, 99))->assertNotFound();
        }
        $this->consultar('FIS-2026-000042', self::SENHA)->assertOk();
    }

    public function test_normaliza_maiusculas_e_espacos(): void
    {
        $this->consultar('  fis-2026-000042 ', strtolower(self::SENHA).' ')->assertOk();
    }

    public function test_fiscalizacao_interna_ou_sem_senha_nao_e_consultavel(): void
    {
        $interna = Fiscalizacao::create(['resultado' => 'Conforme', 'origem' => 'interna']);
        $interna->forceFill(['protocolo' => 'FIS-2026-000043', 'senha_consulta_hash' => Hash::make(self::SENHA)])->saveQuietly();
        $semSenha = Fiscalizacao::create(['resultado' => 'Pendente de apuração', 'origem' => 'peticao']);
        $semSenha->forceFill(['protocolo' => 'FIS-2026-000044'])->saveQuietly();

        $this->consultar('FIS-2026-000043', self::SENHA)->assertNotFound();
        $this->consultar('FIS-2026-000044', self::SENHA)->assertNotFound();
    }

    public function test_limite_de_dez_consultas_por_minuto_por_ip(): void
    {
        foreach (range(1, 10) as $ignored) {
            $this->consultar('FIS-2026-000042', self::SENHA)->assertOk();
        }

        $this->consultar('FIS-2026-000042', self::SENHA)->assertStatus(429);
    }

    public function test_campos_obrigatorios(): void
    {
        $this->postJson('/api/public/denuncias/consulta', [])->assertStatus(422)->assertJsonValidationErrors(['protocolo', 'senha']);
    }
}
