<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function report(Throwable $exception)
    {
        // Chame o método pai para o logging padrão do Laravel
        parent::report($exception);

        // Verifica se o ambiente é de produção para evitar capturas desnecessárias
        // if (app()->environment('production')) {
        try {
            // Salva o log no banco de dados
            ErrorLog::create([
                'type' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'context' => $this->context(),
            ]);
        } catch (\Exception $e) {
            // Loga um erro caso o salvamento do log falhe
            Log::error('Erro ao salvar log de exceção: ' . $e->getMessage());
        }
        // }
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
