<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\NotificationChannelConfig;
use App\Models\ProtocolType;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AuditableObserverTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_criar_editar_e_excluir_gravam_com_o_que_mudou(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->actingAs($admin);

        $type = ProtocolType::create(['codigo' => 'teste', 'nome' => 'Teste', 'ativo' => true, 'ordem' => 1]);
        $created = AuditLog::where('model_type', 'ProtocolType')->where('action', 'CREATE')->first();
        $this->assertNotNull($created);
        $this->assertSame($type->id, (int) $created->model_id);
        $this->assertSame($admin->id, $created->user_id);

        $type->update(['nome' => 'Teste 2']);
        $updated = AuditLog::where('model_type', 'ProtocolType')->where('action', 'UPDATE')->first();
        $this->assertNotNull($updated);
        $this->assertSame(['nome' => 'Teste'], $updated->old_values);
        $this->assertSame(['nome' => 'Teste 2'], $updated->new_values);

        $type->delete();
        $this->assertSame(1, AuditLog::where('model_type', 'ProtocolType')->where('action', 'DELETE')->count());
    }

    public function test_mudanca_apenas_de_timestamp_nao_gera_linha(): void
    {
        $type = ProtocolType::create(['codigo' => 'teste', 'nome' => 'Teste', 'ativo' => true, 'ordem' => 1]);
        $before = AuditLog::where('model_type', 'ProtocolType')->count();

        Carbon::setTestNow(now()->addHour());
        $type->touch();

        $this->assertSame($before, AuditLog::where('model_type', 'ProtocolType')->count());
    }

    public function test_segredos_da_configuracao_de_email_saem_mascarados(): void
    {
        $config = NotificationChannelConfig::current('email');
        $config->update(['configuracao' => ['smtp_host' => 'smtp.exemplo.com', 'smtp_password' => 'segredo-real']]);

        $log = AuditLog::where('model_type', 'NotificationChannelConfig')->where('action', 'UPDATE')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('[mascarado]', $log->new_values['configuracao']['smtp_password']);
        $this->assertSame('smtp.exemplo.com', $log->new_values['configuracao']['smtp_host']);
        $this->assertStringNotContainsString('segredo-real', json_encode($log->getAttributes()));
    }

    public function test_model_fora_da_lista_nao_e_auditado_duas_vezes(): void
    {
        // Vehicle tem observer proprio: deve continuar gerando exatamente 1 linha por criacao.
        Vehicle::create([
            'brand' => 'Fiat', 'model' => 'Uno', 'color' => 'Branco', 'license_plate' => 'ABC1234',
            'renavan' => '12345678901', 'chassis' => '12345678901234567', 'capacity' => 5, 'year' => 2020,
            'id_user' => User::factory()->create(['profile' => 'admin', 'active' => true])->id,
        ]);

        $this->assertSame(1, AuditLog::where('model_type', 'Vehicle')->where('action', 'CREATE')->count());
    }
}
