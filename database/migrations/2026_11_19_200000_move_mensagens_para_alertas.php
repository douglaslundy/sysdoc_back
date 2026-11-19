<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Textos de aviso que estavam no código passam para o banco (tela Alertas):
 *  - protocol_alerts.sistema = mensagem enviada por código próprio (só texto/assunto/ativo são editáveis);
 *  - protocol_alerts.assunto = assunto do e-mail dessas mensagens;
 *  - alertas por módulo SEM template ganham o texto padrão que antes ficava no código.
 * Idempotente e sem apagar nada: não sobrescreve texto já editado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('protocol_alerts', 'sistema')) {
            Schema::table('protocol_alerts', function (Blueprint $table) {
                $table->boolean('sistema')->default(false)->after('prevenir_duplicidade');
                $table->string('assunto', 255)->nullable()->after('template');
                $table->index(['modulo', 'gatilho', 'sistema'], 'protocol_alerts_modulo_gatilho_sistema');
            });
        }

        foreach (config('alert_catalog.sistema', []) as $chave => $item) {
            [$modulo, $gatilho] = explode(':', $chave, 2);
            $existe = DB::table('protocol_alerts')->where('sistema', true)->where('modulo', $modulo)->where('gatilho', $gatilho)->exists();
            if ($existe) {
                continue;
            }
            DB::table('protocol_alerts')->insert([
                'nome' => $item['nome'],
                'descricao' => $item['descricao'],
                'modulo' => $modulo,
                'gatilho' => $gatilho,
                'canais' => json_encode($item['canais']),
                'destinatarios' => json_encode([]),
                'template' => $item['template'],
                'assunto' => $item['assunto'],
                'ativo' => true,
                'prevenir_duplicidade' => false,
                'sistema' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Alertas por módulo já criados sem texto: gravam o padrão que o código usava.
        foreach (config('alert_catalog.padroes', []) as $gatilho => $texto) {
            DB::table('protocol_alerts')->where('sistema', false)->where('gatilho', $gatilho)
                ->where(fn ($q) => $q->whereNull('template')->orWhere('template', ''))
                ->update(['template' => $texto, 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        DB::table('protocol_alerts')->where('sistema', true)->delete();
        Schema::table('protocol_alerts', function (Blueprint $table) {
            $table->dropIndex('protocol_alerts_modulo_gatilho_sistema');
            $table->dropColumn(['sistema', 'assunto']);
        });
    }
};
