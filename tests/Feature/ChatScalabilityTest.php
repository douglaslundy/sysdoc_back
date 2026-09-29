<?php

namespace Tests\Feature;

use App\Exceptions\Handler;
use App\Models\AccessProfile;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatRealtimeConfig;
use App\Models\ErrorLog;
use App\Models\User;
use App\Services\ChatRealtimeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChatScalabilityTest extends TestCase
{
    use RefreshDatabase;

    private function countQueries(callable $callback): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $callback();
        $log = DB::getQueryLog();
        $total = count($log);
        DB::disableQueryLog();
        if (getenv('DEBUG_QUERIES')) {
            fwrite(STDERR, "
".implode("
", array_map(fn ($q) => $q['query'], $log))."
");
        }

        return $total;
    }

    private function conversationBetween(User $a, User $b): ChatConversation
    {
        $conversation = ChatConversation::create(['type' => 'direct', 'created_by' => $a->id, 'last_message_at' => now()]);
        foreach ([$a, $b] as $user) {
            DB::table('chat_conversation_participants')->insert([
                'conversation_id' => $conversation->id, 'user_id' => $user->id, 'joined_at' => now(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $conversation;
    }

    public function test_lista_de_usuarios_do_chat_nao_tem_consulta_por_usuario(): void
    {
        AccessProfile::create(['nome' => 'Gerente', 'slug' => 'manager', 'ativo' => true, 'chat_enabled' => true]);
        AccessProfile::create(['nome' => 'Usuario', 'slug' => 'user', 'ativo' => true, 'chat_enabled' => false]);
        $me = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $small = fn () => $this->actingAs($me, 'sanctum')->getJson('/api/chat/users')->assertOk();

        $small(); // aquecimento: a primeira chamada cria a config padrao do chat (insert unico)

        User::factory()->count(3)->create(['profile' => 'manager', 'active' => true]);
        $queriesFew = $this->countQueries($small);

        User::factory()->count(40)->create(['profile' => 'manager', 'active' => true]);
        $queriesMany = $this->countQueries($small);

        $this->assertSame($queriesFew, $queriesMany, 'A quantidade de consultas nao pode crescer com o numero de usuarios.');
    }

    public function test_lista_de_usuarios_respeita_perfil_e_excecao_individual(): void
    {
        AccessProfile::create(['nome' => 'Gerente', 'slug' => 'manager', 'ativo' => true, 'chat_enabled' => true]);
        AccessProfile::create(['nome' => 'Usuario', 'slug' => 'user', 'ativo' => true, 'chat_enabled' => false]);
        $me = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $porPerfil = User::factory()->create(['profile' => 'manager', 'active' => true]);
        $semAcesso = User::factory()->create(['profile' => 'user', 'active' => true]);
        $comExcecao = User::factory()->create(['profile' => 'user', 'active' => true, 'chat_access_override' => true]);
        $bloqueado = User::factory()->create(['profile' => 'manager', 'active' => true, 'chat_access_override' => false]);
        $inativo = User::factory()->create(['profile' => 'manager', 'active' => false]);

        $ids = collect($this->actingAs($me, 'sanctum')->getJson('/api/chat/users')->assertOk()->json())->pluck('id');

        $this->assertTrue($ids->contains($porPerfil->id));
        $this->assertTrue($ids->contains($comExcecao->id));
        $this->assertFalse($ids->contains($semAcesso->id));
        $this->assertFalse($ids->contains($bloqueado->id));
        $this->assertFalse($ids->contains($inativo->id));
        $this->assertFalse($ids->contains($me->id));
    }

    public function test_conversas_trazem_ultima_mensagem_e_nao_lidas_de_cada_uma_com_consultas_fixas(): void
    {
        $me = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $others = User::factory()->count(6)->create(['profile' => 'admin', 'active' => true]);

        foreach ($others as $i => $other) {
            $conversation = $this->conversationBetween($me, $other);
            ChatMessage::create(['conversation_id' => $conversation->id, 'sender_id' => $other->id, 'body' => "antiga {$i}", 'message_type' => 'text', 'status' => 'sent']);
            ChatMessage::create(['conversation_id' => $conversation->id, 'sender_id' => $other->id, 'body' => "ultima {$i}", 'message_type' => 'text', 'status' => 'sent']);
        }

        $response = null;
        $queries = $this->countQueries(function () use ($me, &$response) {
            $response = $this->actingAs($me, 'sanctum')->getJson('/api/chat/conversations')->assertOk()->json();
        });

        $this->assertCount(6, $response);
        foreach ($response as $item) {
            $this->assertStringStartsWith('ultima', $item['last_message']['body'], 'Cada conversa deve trazer a PROPRIA ultima mensagem.');
            $this->assertSame(2, $item['unread_count']);
        }
        $this->assertLessThan(15, $queries, 'Consultas nao podem crescer por conversa.');
    }

    public function test_heartbeat_sem_mudanca_nao_gera_estatistica_nem_broadcast(): void
    {
        $user = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $payload = ['state' => 'online', 'connection_id' => (string) \Illuminate\Support\Str::uuid(), 'path' => '/'];

        $this->actingAs($user, 'sanctum')->postJson('/api/chat/presence', $payload)->assertOk();
        $eventsAfterFirst = (int) DB::table('chat_usage_daily')->value('connection_events');
        $this->assertSame(1, $eventsAfterFirst);

        $this->actingAs($user, 'sanctum')->postJson('/api/chat/presence', $payload)->assertOk();
        $this->actingAs($user, 'sanctum')->postJson('/api/chat/presence', $payload)->assertOk();

        $this->assertSame(1, (int) DB::table('chat_usage_daily')->value('connection_events'));
        $this->assertSame('online', DB::table('user_presences')->where('user_id', $user->id)->value('status'));

        $payload['state'] = 'away';
        $this->actingAs($user, 'sanctum')->postJson('/api/chat/presence', $payload)->assertOk();
        $this->assertSame(2, (int) DB::table('chat_usage_daily')->value('connection_events'));
    }

    public function test_contadores_diarios_sao_atomicos_e_acumulam(): void
    {
        $service = app(ChatRealtimeService::class);
        $service->increment('messages_sent');
        $service->increment('messages_sent', 4);
        $service->updatePeakConnections(7);
        $service->updatePeakConnections(3);

        $row = DB::table('chat_usage_daily')->first();
        $this->assertSame(5, (int) $row->messages_sent);
        $this->assertSame(7, (int) $row->peak_connections);
        $this->assertSame(1, DB::table('chat_usage_daily')->count());
    }

    public function test_envio_ao_tempo_real_e_adiado_para_depois_da_resposta(): void
    {
        config(['chat.defer_broadcast' => true]);
        $ran = false;

        app(ChatRealtimeService::class)->afterResponse(function () use (&$ran) {
            $ran = true;
        });

        $this->assertFalse($ran, 'Nao deve executar durante a requisicao.');
        $this->app->terminate();
        $this->assertTrue($ran, 'Deve executar ao terminar a requisicao.');
    }

    public function test_config_do_chat_e_lida_do_banco_uma_vez_por_requisicao(): void
    {
        ChatRealtimeConfig::current();
        ChatRealtimeConfig::flushMemo();

        $queries = $this->countQueries(function () {
            for ($i = 0; $i < 10; $i++) {
                ChatRealtimeConfig::rateLimits();
                ChatRealtimeConfig::current();
            }
        });

        $this->assertLessThanOrEqual(2, $queries);
    }

    public function test_alterar_a_config_invalida_a_memoizacao(): void
    {
        $config = ChatRealtimeConfig::current();
        $this->assertSame(300, ChatRealtimeConfig::rateLimits()['rate_limit_sync']);

        $config->update(['rate_limit_sync' => 45]);

        $this->assertSame(45, ChatRealtimeConfig::rateLimits()['rate_limit_sync']);
    }

    public function test_last_used_at_do_token_nao_e_gravado_a_cada_requisicao(): void
    {
        $user = User::factory()->create(['active' => true]);
        $token = $user->createToken('t')->plainTextToken;

        Carbon::setTestNow('2026-03-01 10:00:00');
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/user')->assertOk();
        $first = DB::table('personal_access_tokens')->value('last_used_at');
        $this->assertNotNull($first);

        Carbon::setTestNow('2026-03-01 10:02:00');
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/user')->assertOk();
        $this->assertSame($first, DB::table('personal_access_tokens')->value('last_used_at'));

        Carbon::setTestNow('2026-03-01 10:06:00');
        $this->app['auth']->forgetGuards();
        $this->withHeader('Authorization', 'Bearer '.$token)->getJson('/api/user')->assertOk();
        $this->assertNotSame($first, DB::table('personal_access_tokens')->value('last_used_at'));

        Carbon::setTestNow();
    }

    public function test_erros_429_sao_registrados_no_maximo_uma_vez_por_minuto(): void
    {
        $handler = app(Handler::class);
        $exception = new ThrottleRequestsException('Too Many Attempts.');

        for ($i = 0; $i < 25; $i++) {
            $handler->report($exception);
        }

        $this->assertSame(1, ErrorLog::where('type', ThrottleRequestsException::class)->count());

        $handler->report(new \RuntimeException('outro erro'));
        $handler->report(new \RuntimeException('outro erro'));
        $this->assertSame(2, ErrorLog::where('type', \RuntimeException::class)->count(), 'Erros reais continuam sendo todos registrados.');
    }
}
