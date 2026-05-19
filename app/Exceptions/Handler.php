<?php

namespace App\Exceptions;

use App\Models\ErrorLog;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function report(Throwable $exception)
    {
        parent::report($exception);

        // Evita poluir error_logs com tentativas sem credencial em rotas protegidas.
        if ($exception instanceof AuthenticationException) {
            return;
        }

        // Evita ruído de scanners/crawlers em sistema privado.
        if ($this->shouldIgnoreNotFound($exception)) {
            return;
        }

        $requestUserId = null;
        $requestUserEmail = null;
        $requestUserName = null;

        if (request()) {
            $reqUser = request()->user();
            if ($reqUser) {
                $requestUserId = $reqUser->id ?? null;
                $requestUserEmail = $reqUser->email ?? null;
                $requestUserName = $reqUser->name ?? null;
            }
        }

        $authUserId = Auth::id();
        $sanctumUserId = auth('sanctum')->id();

        // Mantem user_id nulo quando nao for possivel identificar usuario autenticado.
        $userId = $requestUserId ?? $authUserId ?? $sanctumUserId ?? null;

        try {
            ErrorLog::create([
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'user_id' => $userId,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'context' => $this->context($requestUserId, $requestUserName, $requestUserEmail),
            ]);
        } catch (Throwable $e) {
            // O logger de erro nunca deve derrubar/mascarar a excecao original.
            Log::error('Erro ao salvar log de excecao: '.$e->getMessage());
        }
    }

    protected function context($requestUserId = null, $requestUserName = null, $requestUserEmail = null)
    {
        $path = request() ? request()->path() : null;
        $method = request() ? request()->method() : null;
        $ip = request() ? request()->ip() : null;

        return array_merge(parent::context(), [
            'actor_id' => $requestUserId,
            'actor_name' => $requestUserName,
            'actor_email' => $requestUserEmail,
            'method' => $method,
            'path' => $path,
            'ip' => $ip,
            'user' => auth()->check() ? auth()->user()->only(['id', 'email']) : null,
            'url' => request() ? request()->fullUrl() : null,
            'input' => request() ? request()->except(['password', 'password_confirmation']) : [],
        ]);
    }

    protected function shouldIgnoreNotFound(Throwable $exception): bool
    {
        if (! $exception instanceof NotFoundHttpException || ! request()) {
            return false;
        }

        $path = trim((string) request()->path(), '/');
        if ($path === '') {
            return false;
        }

        $ignorePatterns = [
            'sitemap.xml',
            'robots.txt',
            'favicon.ico',
            'file-manager/*',
            'wp-*',
            'wordpress/*',
            '.env',
            '.git/*',
        ];

        foreach ($ignorePatterns as $pattern) {
            if (fnmatch($pattern, $path, FNM_CASEFOLD)) {
                return true;
            }
        }

        return false;
    }
}
