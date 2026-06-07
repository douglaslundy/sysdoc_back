<?php

namespace Tests\Feature;

use App\Models\AttendanceRoom;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AttendanceModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private AttendanceRoom $room;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'profile' => 'admin',
            'active' => true,
        ]);

        $this->room = AttendanceRoom::create([
            'name' => 'Sala 01',
            'description' => 'Sala de atendimento',
            'active' => true,
        ]);
    }

    private function createClient(string $cpf): Client
    {
        return Client::create([
            'name' => 'Cliente '.substr($cpf, -3),
            'mother' => 'Mae Teste',
            'cpf' => $cpf,
            'phone' => '62999999999',
            'born_date' => '1990-01-01',
            'sexo' => 'MASCULINE',
            'active' => true,
        ]);
    }

    public function test_gera_senha_e_insere_na_fila(): void
    {
        $client = $this->createClient('100.000.000-01');

        $create = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attendance/tickets', [
                'clientId' => $client->id,
                'prefix' => 'A',
                'roomId' => $this->room->id,
            ]);

        $create->assertStatus(201)
            ->assertJsonPath('status', 'aguardando')
            ->assertJsonPath('client_id', $client->id)
            ->assertJsonPath('room_id', $this->room->id);

        $queue = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/attendance/queue');

        $queue->assertStatus(200);
        $this->assertCount(1, $queue->json());
        $this->assertSame('aguardando', $queue->json('0.status'));
    }

    public function test_fila_pode_ser_filtrada_por_sala(): void
    {
        $otherRoom = AttendanceRoom::create([
            'name' => 'Sala 02',
            'description' => 'Outra sala',
            'active' => true,
        ]);

        $clientOne = $this->createClient('100.000.000-31');
        $clientTwo = $this->createClient('100.000.000-32');

        $ticketOne = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attendance/tickets', [
                'clientId' => $clientOne->id,
                'roomId' => $this->room->id,
            ])
            ->json();

        $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attendance/tickets', [
                'clientId' => $clientTwo->id,
                'roomId' => $otherRoom->id,
            ])
            ->assertStatus(201);

        $queue = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/attendance/queue?'.http_build_query(['roomId' => $this->room->id]));

        $queue->assertStatus(200);
        $this->assertCount(1, $queue->json());
        $this->assertSame($ticketOne['id'], $queue->json('0.id'));
        $this->assertSame($this->room->id, $queue->json('0.room_id'));
    }

    public function test_chama_proximo_altera_status_e_bloqueia_chamada_duplicada(): void
    {
        $client = $this->createClient('100.000.000-02');

        $ticket = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attendance/tickets', ['clientId' => $client->id, 'roomId' => $this->room->id])
            ->json();

        $call = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attendance/queue/call-next', [
                'roomId' => $this->room->id,
            ]);

        $call->assertStatus(200)
            ->assertJsonPath('id', $ticket['id'])
            ->assertJsonPath('status', 'chamada')
            ->assertJsonPath('room_id', $this->room->id)
            ->assertJsonPath('assigned_user_id', $this->user->id);

        $duplicate = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/queue/{$ticket['id']}/call", [
                'roomId' => $this->room->id,
            ]);

        $duplicate->assertStatus(422);
    }

    public function test_registra_atendimento_e_finaliza(): void
    {
        $client = $this->createClient('100.000.000-03');

        $ticketId = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attendance/tickets', ['clientId' => $client->id, 'roomId' => $this->room->id])
            ->json('id');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/queue/{$ticketId}/call", ['roomId' => $this->room->id])
            ->assertStatus(200);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/service/{$ticketId}/start")
            ->assertStatus(200)
            ->assertJsonPath('status', 'em_atendimento');

        $notes = 'Atendimento realizado com orientacoes e encaminhamento.';

        $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/attendance/service/{$ticketId}/notes", ['notes' => $notes])
            ->assertStatus(200)
            ->assertJsonPath('notes', $notes);

        $finish = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/service/{$ticketId}/finish", ['notes' => $notes])
            ->assertStatus(200)
            ->assertJsonPath('status', 'finalizada');

        $this->assertNotNull($finish->json('finished_at'));
    }

    public function test_retorna_estado_do_painel_com_campos_esperados(): void
    {
        $clientIds = [
            $this->createClient('100.000.000-11')->id,
            $this->createClient('100.000.000-12')->id,
            $this->createClient('100.000.000-13')->id,
            $this->createClient('100.000.000-14')->id,
        ];

        $ticketIds = [];
        foreach ($clientIds as $clientId) {
            $ticketIds[] = $this->actingAs($this->user, 'sanctum')
                ->postJson('/api/attendance/tickets', ['clientId' => $clientId, 'roomId' => $this->room->id])
                ->json('id');
        }

        foreach ($ticketIds as $ticketId) {
            $this->actingAs($this->user, 'sanctum')
                ->postJson("/api/attendance/queue/{$ticketId}/call", ['roomId' => $this->room->id])
                ->assertStatus(200);
        }

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/service/{$ticketIds[0]}/start")
            ->assertStatus(200);

        $oldClientId = $this->createClient('100.000.000-99')->id;
        $oldTicket = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attendance/tickets', ['clientId' => $oldClientId, 'roomId' => $this->room->id])
            ->json();
        $oldTicketId = $oldTicket['id'];
        $oldTicketCode = $oldTicket['display_code'] ?? null;

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/queue/{$oldTicketId}/call", ['roomId' => $this->room->id])
            ->assertStatus(200);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/service/{$oldTicketId}/start")
            ->assertStatus(200);

        $yesterday = now('America/Sao_Paulo')->subDay()->setTime(14, 0)->utc();
        DB::table('attendance_tickets')
            ->where('id', $oldTicketId)
            ->update([
                'created_at' => $yesterday,
                'started_at' => $yesterday,
                'updated_at' => $yesterday,
            ]);

        DB::table('attendance_calls')
            ->where('attendance_ticket_id', $oldTicketId)
            ->update([
                'called_at' => $yesterday,
                'created_at' => $yesterday,
                'updated_at' => $yesterday,
            ]);

        $panel = $this->getJson('/api/attendance/panel/state');

        $panel->assertStatus(200)
            ->assertJsonStructure([
                'currentCall' => ['ticketCode', 'clientName', 'roomName', 'userName', 'calledAt'],
                'currentInService',
                'lastCalls',
            ]);

        $this->assertCount(3, $panel->json('lastCalls'));
        $this->assertNotEmpty($panel->json('currentInService'));
        if ($oldTicketCode) {
            $this->assertNotContains($oldTicketCode, array_column($panel->json('currentInService'), 'ticketCode'));
            $this->assertNotContains($oldTicketCode, array_column($panel->json('lastCalls'), 'ticketCode'));
        }
    }

    public function test_lista_atendimentos_com_filtros_de_sala_usuario_status_e_periodo(): void
    {
        $client = $this->createClient('100.000.000-21');

        $ticketId = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/attendance/tickets', ['clientId' => $client->id, 'roomId' => $this->room->id])
            ->json('id');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/queue/{$ticketId}/call", ['roomId' => $this->room->id])
            ->assertStatus(200);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/service/{$ticketId}/start")
            ->assertStatus(200);

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/attendance/service/{$ticketId}/finish", ['notes' => 'ok'])
            ->assertStatus(200);

        $from = now()->subDay()->toDateString();
        $to = now()->addDay()->toDateString();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/attendance/tickets?'.http_build_query([
                'status' => 'finalizada',
                'roomId' => $this->room->id,
                'assignedUserId' => $this->user->id,
                'serviceFrom' => $from,
                'serviceTo' => $to,
            ]));

        $response->assertStatus(200);
        $this->assertNotEmpty($response->json());
        $this->assertSame($ticketId, $response->json('0.id'));
    }
}
