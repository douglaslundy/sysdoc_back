<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Fiscalizacao;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DenunciaPublicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Storage::fake('private');
    }

    private function payload(array $extra = []): array
    {
        return $extra + [
            'assunto' => 'Falta de higiene',
            'descricao_denuncia' => 'Alimentos expostos sem refrigeração.',
            'local_endereco' => 'Rua das Flores, 100 - Centro',
        ];
    }

    private function enviar(array $dados, string $ip = '10.1.1.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->postJson('/api/public/denuncias', $dados);
    }

    public function test_denuncia_minima_sem_identificacao_cria_fiscalizacao_pendente(): void
    {
        $response = $this->enviar($this->payload())->assertCreated();

        $this->assertMatchesRegularExpression('/^FIS-\d{4}-\d{6}$/', $response->json('protocolo'));
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]{8}$/', $response->json('senha'));

        $f = Fiscalizacao::where('protocolo', $response->json('protocolo'))->firstOrFail();
        $this->assertSame('denuncia', $f->origem);
        $this->assertSame('Pendente de apuração', $f->resultado);
        $this->assertNull($f->fiscal_id);
        $this->assertNull($f->estabelecimento_id);
        $this->assertNull($f->denunciante_nome);
        $this->assertTrue(Hash::check($response->json('senha'), $f->getRawOriginal('senha_consulta_hash')));

        $mov = $f->movimentacoes()->get();
        $this->assertCount(1, $mov);
        $this->assertSame('denuncia_recebida', $mov[0]->acao);
        $this->assertTrue($mov[0]->publico);
        $this->assertNull($mov[0]->user_id);
    }

    public function test_url_de_consulta_usa_a_url_do_sistema_e_nunca_leva_a_senha(): void
    {
        config(['app.frontend_url' => 'https://sistema.exemplo.gov.br']);

        $response = $this->enviar($this->payload())->assertCreated();

        $this->assertSame(
            'https://sistema.exemplo.gov.br/petition/track?protocolo='.$response->json('protocolo'),
            $response->json('url_consulta')
        );
        $this->assertStringNotContainsString($response->json('senha'), $response->json('url_consulta'));
    }

    public function test_identificacao_estabelecimento_informado_e_anexos_sao_guardados(): void
    {
        $response = $this->enviar($this->payload([
            'denunciante_nome' => 'Maria', 'denunciante_contato' => '35 99999-0000',
            'estabelecimento_nome_informado' => 'Bar do Zé',
            'files' => [UploadedFile::fake()->image('foto.jpg'), UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf')],
        ]))->assertCreated();

        $f = Fiscalizacao::where('protocolo', $response->json('protocolo'))->firstOrFail();
        $this->assertSame('Maria', $f->denunciante_nome);
        $this->assertSame('Bar do Zé', $f->estabelecimento_nome_informado);

        $attachments = $f->attachments()->get();
        $this->assertCount(2, $attachments);
        $this->assertSame(['denunciante', 'denunciante'], $attachments->pluck('origem')->all());
        $this->assertNull($attachments[0]->uploaded_by);
        foreach ($attachments as $attachment) {
            Storage::disk('private')->assertExists($attachment->path);
        }
    }

    public function test_campo_isca_preenchido_devolve_sucesso_falso_sem_gravar(): void
    {
        $response = $this->enviar($this->payload(['website' => 'http://spam.example']))->assertCreated();

        $this->assertNotEmpty($response->json('protocolo'));
        $this->assertSame(0, Fiscalizacao::count());
    }

    public function test_limite_de_cinco_denuncias_por_hora_por_ip(): void
    {
        foreach (range(1, 5) as $ignored) {
            $this->enviar($this->payload())->assertCreated();
        }

        $this->enviar($this->payload())->assertStatus(429);
        $this->enviar($this->payload(), '10.2.2.2')->assertCreated(); // outro IP não é afetado
    }

    public function test_validacao_e_limites_de_arquivo(): void
    {
        $this->enviar(['assunto' => 'x'])->assertStatus(422)->assertJsonValidationErrors(['descricao_denuncia', 'local_endereco']);

        $this->enviar($this->payload(['files' => [UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload')]]))
            ->assertStatus(422);

        $seis = array_map(fn ($i) => UploadedFile::fake()->image("f{$i}.jpg"), range(1, 6));
        $this->enviar($this->payload(['files' => $seis]))->assertStatus(422);

        $this->enviar($this->payload(['files' => [UploadedFile::fake()->create('grande.pdf', 11000, 'application/pdf')]]))
            ->assertStatus(422);

        $this->assertSame(0, Fiscalizacao::count());
    }

    public function test_senha_nunca_aparece_na_auditoria_nem_na_api_interna(): void
    {
        $senha = $this->enviar($this->payload())->assertCreated()->json('senha');
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $this->assertStringNotContainsString($senha, json_encode(AuditLog::all()->toArray()));
        $this->assertStringNotContainsString('senha_consulta_hash', json_encode(AuditLog::all()->toArray()) ?: '');

        $json = $this->actingAs($admin, 'sanctum')->getJson('/api/fiscalizacoes')->assertOk()->getContent();
        $this->assertStringNotContainsString($senha, $json);
        $this->assertStringNotContainsString('senha_consulta_hash', $json);
    }

    public function test_denuncia_aparece_na_listagem_interna_como_pendente(): void
    {
        $protocolo = $this->enviar($this->payload())->assertCreated()->json('protocolo');
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $this->actingAs($admin, 'sanctum')->getJson('/api/fiscalizacoes?origem=denuncia')
            ->assertOk()
            ->assertJsonPath('data.0.protocolo', $protocolo)
            ->assertJsonPath('data.0.resultado', 'Pendente de apuração');
    }
}
