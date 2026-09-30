<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\Queue;
use App\Models\Speciality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function legacyLog(string $model, int $modelId): AuditLog
    {
        return AuditLog::create([
            'user_id' => null, 'user_name' => 'Legado', 'action' => 'UPDATE', 'model_type' => $model, 'model_id' => $modelId,
            'client_id' => null, 'endpoint' => 'api/x', 'method' => 'PUT', 'ip_address' => '127.0.0.1', 'created_at' => now(),
        ]);
    }

    public function test_registros_antigos_recebem_o_cidadao_quando_e_possivel_deriva_lo(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $client = Client::withoutEvents(fn () => Client::create([
            'name' => 'Maria', 'mother' => 'M', 'cpf' => '111.222.333-44', 'born_date' => '1990-01-01', 'active' => true,
        ]));
        $speciality = Speciality::create(['id_user' => $admin->id, 'name' => 'Fisio']);
        $queue = Queue::create([
            'id_client' => $client->id, 'id_specialities' => $speciality->id, 'id_user' => $admin->id,
            'done' => false, 'urgency' => false,
        ]);
        AuditLog::query()->delete();

        $clientLog = $this->legacyLog('Client', $client->id);
        $queueLog = $this->legacyLog('Queue', $queue->id);
        $orphanLog = $this->legacyLog('Queue', 987654);      // fila que nao existe mais
        $vehicleLog = $this->legacyLog('Vehicle', 1);        // sem vinculo com cidadao

        (require base_path('database/migrations/2026_09_30_000003_backfill_audit_logs_client_id.php'))->up();

        $this->assertSame($client->id, (int) $clientLog->fresh()->client_id);
        $this->assertSame($client->id, (int) $queueLog->fresh()->client_id);
        $this->assertNull($orphanLog->fresh()->client_id);
        $this->assertNull($vehicleLog->fresh()->client_id);
    }

    public function test_nao_sobrescreve_vinculo_existente_e_pode_rodar_duas_vezes(): void
    {
        $client = Client::withoutEvents(fn () => Client::create([
            'name' => 'Maria', 'mother' => 'M', 'cpf' => '111.222.333-44', 'born_date' => '1990-01-01', 'active' => true,
        ]));
        AuditLog::query()->delete();
        $log = $this->legacyLog('Client', $client->id);
        $log->update(['client_id' => 4242]);

        $migration = require base_path('database/migrations/2026_09_30_000003_backfill_audit_logs_client_id.php');
        $migration->up();
        $migration->up();

        $this->assertSame(4242, (int) $log->fresh()->client_id);
    }
}
