<?php

namespace App\Exceptions;

use App\Models\ErrorLog;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

        // Nunca use 0 para user_id: em casos não autenticados deve ser null,
        // evitando violação de FK em error_logs.user_id.
        $userId = Auth::check() ? Auth::id() : null;

        try {
            ErrorLog::create([
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'user_id' => $userId,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'context' => $this->context(),
            ]);
        } catch (Throwable $e) {
            // O logger de erro nunca deve derrubar/mascarar a exceção original.
            Log::error('Erro ao salvar log de exceção: ' . $e->getMessage());
        }
    }

    protected function context()
    {
        return array_merge(parent::context(), [
            'user' => auth()->check() ? auth()->user()->only(['id', 'email']) : null,
            'url' => request()->fullUrl(),
            'input' => request()->except(['password', 'password_confirmation']),
        ]);
    }
}

