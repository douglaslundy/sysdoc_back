<?php

namespace App\Providers;

use App\Models\AccessProfile;
use App\Models\ChatRealtimeConfig;
use App\Models\CategoriaExame;
use App\Models\Client;
use App\Models\Exame;
use App\Models\Letter;
use App\Models\MedicineDailyStatus;
use App\Models\MedicineItem;
use App\Models\MedicineMonthlyAcquisition;
use App\Models\MedicoSolicitante;
use App\Models\Ordinance;
use App\Models\PageCategory;
use App\Models\PedidoExame;
use App\Models\ResultadoExame;
use App\Models\Route;
use App\Models\Speciality;
use App\Models\SystemPage;
use App\Models\Trip;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\Alvara;
use App\Models\Estabelecimento;
use App\Models\Fiscalizacao;
use App\Models\VigilanciaConfig;
use App\Observers\AccessProfileObserver;
use App\Observers\AlvaraObserver;
use App\Observers\EstabelecimentoObserver;
use App\Observers\FiscalizacaoObserver;
use App\Observers\CategoriaExameObserver;
use App\Observers\ClientObserver;
use App\Observers\ExameObserver;
use App\Observers\LetterObserver;
use App\Observers\MedicineDailyStatusObserver;
use App\Observers\MedicineItemObserver;
use App\Observers\MedicineMonthlyAcquisitionObserver;
use App\Observers\MedicoSolicitanteObserver;
use App\Observers\OrdinanceObserver;
use App\Observers\PageCategoryObserver;
use App\Observers\PedidoExameObserver;
use App\Observers\ResultadoExameObserver;
use App\Observers\RouteObserver;
use App\Observers\SpecialityObserver;
use App\Observers\SystemPageObserver;
use App\Observers\TripObserver;
use App\Observers\UserObserver;
use App\Observers\VehicleObserver;
use App\Observers\VigilanciaConfigObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\Secretarias\ActiveSecretaria::class);
    }

    public function boot(): void
    {
        $this->configureRateLimiting();

        Sanctum::usePersonalAccessTokenModel(\App\Models\PersonalAccessToken::class);

        app(\App\Services\ChatBroadcastConfigService::class)->apply();
        User::observe(UserObserver::class);

        // Auditoria generica para os models listados em config/audit.php.
        foreach (config('audit.models', []) as $auditedModel) {
            $auditedModel::observe(\App\Observers\AuditableObserver::class);
        }

        Client::observe(ClientObserver::class);
        PedidoExame::observe(PedidoExameObserver::class);
        ResultadoExame::observe(ResultadoExameObserver::class);
        Trip::observe(TripObserver::class);
        AccessProfile::observe(AccessProfileObserver::class);
        Speciality::observe(SpecialityObserver::class);
        MedicineItem::observe(MedicineItemObserver::class);
        MedicineDailyStatus::observe(MedicineDailyStatusObserver::class);
        MedicineMonthlyAcquisition::observe(MedicineMonthlyAcquisitionObserver::class);
        VigilanciaConfig::observe(VigilanciaConfigObserver::class);
        Exame::observe(ExameObserver::class);
        CategoriaExame::observe(CategoriaExameObserver::class);
        MedicoSolicitante::observe(MedicoSolicitanteObserver::class);
        Vehicle::observe(VehicleObserver::class);
        Route::observe(RouteObserver::class);
        Letter::observe(LetterObserver::class);
        Ordinance::observe(OrdinanceObserver::class);
        Estabelecimento::observe(EstabelecimentoObserver::class);
        Alvara::observe(AlvaraObserver::class);
        Fiscalizacao::observe(FiscalizacaoObserver::class);
        SystemPage::observe(SystemPageObserver::class);
        PageCategory::observe(PageCategoryObserver::class);
    }

    /**
     * Chave do limitador do chat: usuario Sanctum (nunca o IP de quem esta logado).
     * O guard padrao e 'api' (jwt) e nao le o token Sanctum; cair no IP faria todos os
     * usuarios de uma prefeitura atras do mesmo link dividirem um unico balde.
     */
    protected function chaveChat(Request $request): string
    {
        $userId = optional($request->user('sanctum'))->id;

        return $userId ? 'user:'.$userId : 'ip:'.$request->ip();
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            // IMPORTANTE: o guard default e 'api' (driver jwt) e nao le o token
            // Sanctum que o frontend envia. Sem resolver pelo guard 'sanctum'
            // aqui, $request->user() volta null e TODOS os usuarios autenticados
            // de um mesmo IP (ex: uma secretaria inteira atras de um unico link)
            // dividiam o mesmo balde de 120/min -> 429 em massa.
            $userId = optional($request->user('sanctum'))->id;

            // Teto por usuario (600/min = 10 req/s) e por IP para nao autenticados.
            // O balde por IP e COMPARTILHADO por todos os usuarios atras do mesmo link
            // (uma prefeitura inteira sai por 1 IP), por isso e bem mais folgado.
            return $userId
                ? Limit::perMinute(1500)->by('user:'.$userId)
                : Limit::perMinute(240)->by('ip:'.$request->ip());
        });

        RateLimiter::for('chat-sync', function (Request $request) {
            $limits = ChatRealtimeConfig::rateLimits();
            $limit = $limits['rate_limit_sync'] === 0 ? 5000 : max($limits['rate_limit_sync'], 600);

            return Limit::perMinutes($limits['rate_limit_decay_minutes'], $limit)
                ->by($this->chaveChat($request))
                ->response(function () {
                    return response()->json([
                        'message' => 'Muitas sincronizações do chat em sequência. Aguarde um instante e tente novamente.',
                    ], 429);
                });
        });

        RateLimiter::for('chat-message', function (Request $request) {
            $limits = ChatRealtimeConfig::rateLimits();
            $limit = $limits['rate_limit_messages'] === 0 ? 5000 : $limits['rate_limit_messages'];

            return Limit::perMinutes($limits['rate_limit_decay_minutes'], $limit)
                ->by($this->chaveChat($request))
                ->response(function () {
                    return response()->json([
                        'message' => 'Limite de envio de mensagens atingido. Aguarde um instante para enviar novamente.',
                    ], 429);
                });
        });

        RateLimiter::for('chat-typing', function (Request $request) {
            $limits = ChatRealtimeConfig::rateLimits();
            $limit = $limits['rate_limit_typing'] === 0 ? 5000 : $limits['rate_limit_typing'];

            return Limit::perMinutes($limits['rate_limit_decay_minutes'], $limit)
                ->by($this->chaveChat($request))
                ->response(function () {
                    return response()->json([
                        'message' => 'Muitos eventos de digitação em sequência. Aguarde um instante e tente novamente.',
                    ], 429);
                });
        });

        RateLimiter::for('chat-presence', function (Request $request) {
            $limits = ChatRealtimeConfig::rateLimits();
            $limit = $limits['rate_limit_presence'] === 0 ? 5000 : max($limits['rate_limit_presence'], 600);

            return Limit::perMinutes($limits['rate_limit_decay_minutes'], $limit)
                ->by($this->chaveChat($request))
                ->response(function () {
                    return response()->json([
                        'message' => 'Muitas atualizações de presença em sequência. Aguarde um instante e tente novamente.',
                    ], 429);
                });
        });

        RateLimiter::for('login', function (Request $request) {
            $cpf = (string) $request->input('cpf', '');
            $key = sprintf('login|%s|%s', preg_replace('/\D/', '', $cpf), $request->ip());

            return Limit::perMinutes(1, 5)
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'message' => 'Muitas tentativas de login. Aguarde um instante e tente novamente.',
                    ], 429);
                });
        });

        // Denúncia pública: no máximo 5 por hora por IP.
        RateLimiter::for('denuncia-create', function (Request $request) {
            return Limit::perHour(5)
                ->by('denuncia-create|'.$request->ip())
                ->response(fn () => response()->json([
                    'message' => 'Muitas denúncias enviadas deste local. Aguarde um pouco e tente novamente.',
                ], 429));
        });

        // Consulta da denúncia: 10 por minuto por IP (o bloqueio por protocolo fica no controller).
        RateLimiter::for('denuncia-consulta', function (Request $request) {
            return Limit::perMinute(10)
                ->by('denuncia-consulta|'.$request->ip())
                ->response(fn () => response()->json([
                    'error' => 'Muitas consultas. Aguarde um instante e tente novamente.',
                ], 429));
        });

        // Pedido público de cadastro: 5 por hora por IP; leituras (lista/desafio): 30 por minuto.
        RateLimiter::for('cadastro-publico', fn (Request $request) => Limit::perHour(5)
            ->by('cadastro-publico|'.$request->ip())
            ->response(fn () => response()->json(['message' => 'Muitos pedidos deste local. Tente novamente mais tarde.'], 429)));
        RateLimiter::for('cadastro-publico-leitura', fn (Request $request) => Limit::perMinute(30)
            ->by('cadastro-publico-leitura|'.$request->ip())
            ->response(fn () => response()->json(['message' => 'Muitas requisições. Aguarde um instante.'], 429)));

        // Ouvidoria pública: registro 5/h por IP; consulta 10/min por IP (o bloqueio por protocolo fica no controller).
        RateLimiter::for('ouvidoria-create', fn (Request $request) => Limit::perHour(5)
            ->by('ouvidoria-create|'.$request->ip())
            ->response(fn () => response()->json(['message' => 'Muitos registros deste local. Tente novamente mais tarde.'], 429)));
        RateLimiter::for('ouvidoria-consulta', fn (Request $request) => Limit::perMinute(10)
            ->by('ouvidoria-consulta|'.$request->ip())
            ->response(fn () => response()->json(['message' => 'Muitas consultas. Aguarde um instante.'], 429)));
        RateLimiter::for('ouvidoria-leitura', fn (Request $request) => Limit::perMinute(30)
            ->by('ouvidoria-leitura|'.$request->ip())
            ->response(fn () => response()->json(['message' => 'Muitas requisições. Aguarde um instante.'], 429)));

        RateLimiter::for('forgot-password', function (Request $request) {
            $email = (string) $request->input('email', '');
            $key = sprintf('forgot-password|%s|%s', mb_strtolower(trim($email)), $request->ip());

            return Limit::perMinutes(5, 5)
                ->by($key)
                ->response(function () {
                    return response()->json([
                        'message' => 'Muitas tentativas de envio. Aguarde alguns minutos e tente novamente.',
                        'reason' => 'Proteção contra abuso para recuperação de senha.',
                    ], 429);
                });
        });
    }
}
