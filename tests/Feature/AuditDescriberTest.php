<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Services\Audit\AuditDescriber;
use Tests\TestCase;

class AuditDescriberTest extends TestCase
{
    private function log(string $action, ?string $model, ?array $old = null, ?array $new = null): AuditLog
    {
        return new AuditLog([
            'action' => $action, 'model_type' => $model, 'old_values' => $old, 'new_values' => $new,
        ]);
    }

    /** @dataProvider titulos */
    public function test_titulo_em_portugues(string $action, ?string $model, ?array $old, ?array $new, string $esperado): void
    {
        $this->assertSame($esperado, AuditDescriber::describe($this->log($action, $model, $old, $new))['titulo']);
    }

    public static function titulos(): array
    {
        return [
            'visualizou cadastro' => ['VIEW', 'Client', null, null, 'Visualizou o cadastro'],
            'criou cadastro' => ['CREATE', 'Client', null, ['name' => 'A'], 'Cadastrou o cidadão'],
            'editou cadastro' => ['UPDATE', 'Client', ['name' => 'A'], ['name' => 'B'], 'Editou o cadastro'],
            'excluiu cadastro' => ['DELETE', 'Client', ['name' => 'A'], null, 'Excluiu o cadastro'],
            'consultou relatorio' => ['VIEW_REPORT', 'Client', null, null, 'Consultou o relatório do cidadão'],
            'entrou na fila' => ['CREATE', 'Queue', null, [], 'Inseriu na fila'],
            'deu baixa' => ['UPDATE', 'Queue', ['done' => 0], ['done' => 1], 'Deu baixa na fila'],
            'reabriu' => ['UPDATE', 'Queue', ['done' => 1], ['done' => 0], 'Reabriu item da fila'],
            'editou fila' => ['UPDATE', 'Queue', ['obs' => 'a'], ['obs' => 'b'], 'Editou item da fila'],
            'removeu da fila' => ['DELETE', 'Queue', [], null, 'Removeu da fila'],
            'anexo fila' => ['CREATE_ATTACHMENT', 'Queue', null, [], 'Anexou arquivo na fila'],
            'viagem inserir' => ['CREATE', 'TripClient', null, [], 'Inseriu em viagem'],
            'viagem remover' => ['DELETE', 'TripClient', [], null, 'Removeu de viagem'],
            'viagem confirmar' => ['UPDATE', 'TripClient', ['is_confirmed' => 0], ['is_confirmed' => 1], 'Confirmou viagem'],
            'viagem desconfirmar' => ['UPDATE', 'TripClient', ['is_confirmed' => 1], ['is_confirmed' => 0], 'Desfez a confirmação da viagem'],
            'pedido exame' => ['CREATE', 'PedidoExame', null, [], 'Solicitou exame'],
            'acao desconhecida' => ['ALGUMA_COISA_NOVA', null, null, null, 'Alguma coisa nova'],
        ];
    }

    public function test_detalhe_mostra_de_para_com_rotulos_e_ignora_campos_internos(): void
    {
        $detalhe = AuditDescriber::describe($this->log(
            'UPDATE',
            'Client',
            ['name' => 'Maria', 'phone' => '111'],
            ['name' => 'Maria Silva', 'phone' => '222', '__audit_subject_name' => 'Maria Silva']
        ))['detalhe'];

        $this->assertStringContainsString('Nome: Maria → Maria Silva', $detalhe);
        $this->assertStringContainsString('Telefone: 111 → 222', $detalhe);
        $this->assertStringNotContainsString('__audit_subject_name', $detalhe);
    }

    public function test_detalhe_limita_campos_e_tamanho_dos_valores(): void
    {
        $old = [];
        $new = [];
        foreach (range(1, 10) as $i) {
            $old["campo_$i"] = 'a';
            $new["campo_$i"] = str_repeat('x', 200);
        }

        $detalhe = AuditDescriber::describe($this->log('UPDATE', 'Client', $old, $new))['detalhe'];

        $this->assertLessThanOrEqual(7, substr_count($detalhe, "\n") + 1, 'No máximo 6 campos + resumo.');
        $this->assertStringContainsString('…', $detalhe);
    }

    public function test_sem_alteracoes_o_detalhe_e_nulo(): void
    {
        $this->assertNull(AuditDescriber::describe($this->log('VIEW', 'Client'))['detalhe']);
    }

    public function test_valores_de_segredo_mascarado_continuam_mascarados(): void
    {
        $detalhe = AuditDescriber::describe($this->log('UPDATE', null, ['password' => '[mascarado]'], ['password' => '[mascarado]']))['detalhe'];

        $this->assertStringNotContainsString('senha-real', (string) $detalhe);
    }
}
