<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolType;
use App\Models\ProtocolUserUnit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LetterProtocolTest extends TestCase
{
    use RefreshDatabase;

    public function test_usuario_cria_protocolo_com_pdf_do_oficio_anexado(): void
    {
        Storage::fake('public');

        $sender = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $destination = User::factory()->create(['active' => true]);
        $originUnit = ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'nome' => 'Secretaria de Origem',
            'ativo' => true,
        ]);
        $destinationUnit = ProtocolOrganizationalUnit::create([
            'tipo' => 'secretaria',
            'nome' => 'Secretaria de Destino',
            'ativo' => true,
        ]);
        ProtocolUserUnit::create([
            'user_id' => $sender->id,
            'protocol_organizational_unit_id' => $originUnit->id,
            'papel' => 'lotacao',
            'ativo' => true,
        ]);
        ProtocolUserUnit::create([
            'user_id' => $destination->id,
            'protocol_organizational_unit_id' => $destinationUnit->id,
            'papel' => 'lotacao',
            'ativo' => true,
        ]);
        ProtocolType::create([
            'codigo' => 'oficio',
            'nome' => 'Ofício',
            'ativo' => true,
            'ordem' => 1,
        ]);
        $letter = Letter::create([
            'id_user' => $sender->id,
            'number' => 15,
            'subject_matter' => 'Solicitação de providências',
            'sender' => 'Secretaria de Origem',
            'recipient' => 'Secretaria de Destino',
            'summary' => 'Resumo do ofício.',
            'obs' => 'Conteúdo completo do ofício.',
        ]);

        $response = $this->actingAs($sender, 'sanctum')
            ->postJson("/api/letters/{$letter->id}/protocol", [
                'destino_user_id' => $destination->id,
            ])
            ->assertCreated()
            ->assertJsonPath('message', 'Protocolo criado com sucesso.')
            ->assertJsonPath('protocol.tipo', 'oficio')
            ->assertJsonPath('protocol.origem_unit_id', $originUnit->id)
            ->assertJsonPath('protocol.destino_unit_id', $destinationUnit->id)
            ->assertJsonPath('protocol.responsavel_atual_id', $destination->id);

        $protocolId = $response->json('protocol.id');
        $attachmentId = $response->json('protocol.attachments.0.id');
        $attachmentPath = $response->json('protocol.attachments.0.caminho');

        $this->assertDatabaseHas('protocol_movements', [
            'protocol_id' => $protocolId,
            'acao' => 'criado_por_oficio',
            'to_user_id' => $destination->id,
        ]);
        $this->assertDatabaseHas('kanban_tasks', [
            'protocol_id' => $protocolId,
            'status' => 'novo',
            'responsavel_id' => $destination->id,
        ]);
        $this->assertDatabaseHas('protocol_attachments', [
            'protocol_id' => $protocolId,
            'mime_type' => 'application/pdf',
            'ativo' => true,
        ]);
        Storage::disk('public')->assertExists($attachmentPath);

        $this->actingAs($destination, 'sanctum')
            ->getJson("/api/protocolos/{$protocolId}")
            ->assertOk()
            ->assertJsonPath('id', $protocolId);
        $this->actingAs($destination, 'sanctum')
            ->get("/api/protocolos/anexos/{$attachmentId}/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_usuario_sem_unidade_vinculada_nao_cria_protocolo_pelo_oficio(): void
    {
        $sender = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $destination = User::factory()->create(['active' => true]);
        $letter = Letter::create([
            'id_user' => $sender->id,
            'number' => 16,
            'subject_matter' => 'Teste',
            'sender' => 'Origem',
            'recipient' => 'Destino',
        ]);

        $this->actingAs($sender, 'sanctum')
            ->postJson("/api/letters/{$letter->id}/protocol", [
                'destino_user_id' => $destination->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath(
                'message',
                'Seu usuário não possui secretaria ou unidade vinculada para criar o protocolo.'
            );
    }
}
