<?php

use App\Exceptions\Handler;
use App\Http\Middleware;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware as MiddlewareConfig;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (MiddlewareConfig $middleware) {
        // Pilha global: o bloqueio de IP vem logo após o TrustProxies e o CORS.
        $middleware->use([
            Middleware\TrustProxies::class,
            // CORS antes do bloqueio: o 403 do IP banido leva os cabecalhos CORS e o navegador mostra
            // "Acesso bloqueado" em vez de um falso erro de CORS.
            \Illuminate\Http\Middleware\HandleCors::class,
            Middleware\BlockBannedIps::class,
            Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        $middleware->group('web', [
            Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

        $middleware->group('api', [
            Middleware\BeginSecretariaContext::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            Middleware\LogUserAction::class,
        ]);

        $middleware->alias([
            'auth' => Middleware\Authenticate::class,
            'guest' => Middleware\RedirectIfAuthenticated::class,
            'admin' => Middleware\AdminOnly::class,
            'audit.read' => Middleware\AuditReadAccess::class,
            'equipe.aps' => Middleware\EnsureEquipeAps::class,
            'chat.access' => Middleware\EnsureChatAccess::class,
            'almoxarifado.permission' => Middleware\EnsureAlmoxarifadoPermission::class,
            'page.permission' => Middleware\EnsurePagePermission::class,
            'secretaria.active' => Middleware\ResolveActiveSecretaria::class,
            'secretaria.only' => Middleware\OnlySecretaria::class,
            'route.pages' => Middleware\EnforceRoutePagePolicy::class,
        ]);

        // A secretaria ativa é definida após a autenticação e antes do route-model binding.
        $middleware->prependToPriorityList(before: AuthenticatesRequests::class, prepend: Middleware\BeginSecretariaContext::class);
        $middleware->appendToPriorityList(after: AuthenticatesRequests::class, append: Middleware\ResolveActiveSecretaria::class);
    })
    ->create();

// O Handler próprio grava os erros em error_logs, conta 404 de scanners e limita o log de 429.
$app->singleton(ExceptionHandler::class, Handler::class);

return $app;
