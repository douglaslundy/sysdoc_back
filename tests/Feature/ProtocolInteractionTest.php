<?php

namespace Tests\Feature;

use App\Models\Protocol;
use App\Models\ProtocolAttachment;
use App\Models\ProtocolView;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProtocolInteractionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Protocol $protocol;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $this->protocol = Protocol::create([
            'numero' => 'PRT-TESTE-001',
            'assunto' => 'Protocolo para testes de interacao',
            'tipo' => 'administrativo',
            'status' => 'novo',
            'prioridade' => 'normal',
            'solicitante_tipo' => 'interno',
            'responsavel_atual_id' => $this->user->id,
            'criado_por_id' => $this->user->id,
            'novo' => true,
        ]);
    }

    public function test_recarregar_detalhes_nao_duplica_visualizacao_recente(): void
    {
        $url = "/api/protocolos/{$this->protocol->id}";

        $this->actingAs($this->user, 'sanctum')
            ->getJson($url.'?view_session=aba-protocolo-1')
            ->assertOk();

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/protocolos/{$this->protocol->id}/comentarios", [
                'conteudo' => 'Comentario sem nova visualizacao.',
            ])
            ->assertOk();

        $this->actingAs($this->user, 'sanctum')
            ->getJson($url.'?view_session=aba-protocolo-1')
            ->assertOk();

        $this->assertDatabaseCount('protocol_views', 1);
        $this->assertDatabaseHas('protocol_views', [
            'protocol_id' => $this->protocol->id,
            'user_id' => $this->user->id,
            'session_key' => 'aba-protocolo-1',
        ]);
    }

    public function test_nova_visita_e_registrada_apos_janela_de_deduplicacao(): void
    {
        ProtocolView::create([
            'protocol_id' => $this->protocol->id,
            'user_id' => $this->user->id,
            'visualized_at' => now()->subMinutes(31),
        ]);

        $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/protocolos/{$this->protocol->id}")
            ->assertOk();

        $this->assertDatabaseCount('protocol_views', 2);
    }

    public function test_download_do_anexo_retorna_o_arquivo_com_nome_original(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('protocolos/arquivo-teste.pdf', 'conteudo-pdf');

        $attachment = ProtocolAttachment::create([
            'protocol_id' => $this->protocol->id,
            'user_id' => $this->user->id,
            'nome_original' => 'documento teste.pdf',
            'caminho' => 'protocolos/arquivo-teste.pdf',
            'mime_type' => 'application/pdf',
            'tamanho_bytes' => 12,
            'ativo' => true,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->get("/api/protocolos/anexos/{$attachment->id}/download");

        $response->assertOk();
        $response->assertDownload('documento teste.pdf');
        $this->assertSame(
            'conteudo-pdf',
            file_get_contents($response->baseResponse->getFile()->getPathname())
        );
    }
}
