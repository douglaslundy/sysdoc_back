<?php

namespace Tests\Feature;

use App\Models\ErrorLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeErrorLogsCommandTest extends TestCase
{
    use RefreshDatabase;

    private function erro(string $mensagem, int $diasAtras): ErrorLog
    {
        $log = ErrorLog::create(['type' => 'Exception', 'message' => $mensagem]);
        ErrorLog::whereKey($log->id)->update(['created_at' => now()->subDays($diasAtras)]);

        return $log;
    }

    public function test_apaga_erros_mais_antigos_que_o_limite(): void
    {
        $velho = $this->erro('velho', 120);
        $novo = $this->erro('novo', 10);

        $this->artisan('logs:limpar-erros', ['--dias' => 90])->assertSuccessful();

        $this->assertDatabaseMissing('error_logs', ['id' => $velho->id]);
        $this->assertDatabaseHas('error_logs', ['id' => $novo->id]);
    }

    public function test_usa_retencao_padrao_de_90_dias(): void
    {
        $limite = $this->erro('no limite', 89);
        $velho = $this->erro('velho', 91);

        $this->artisan('logs:limpar-erros')->assertSuccessful();

        $this->assertDatabaseHas('error_logs', ['id' => $limite->id]);
        $this->assertDatabaseMissing('error_logs', ['id' => $velho->id]);
    }
}
