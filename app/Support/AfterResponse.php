<?php

namespace App\Support;

/**
 * Executa trabalho lento (chamadas a WhatsApp/e-mail/tempo real) DEPOIS de a
 * resposta HTTP ter sido entregue ao usuario. Sem isso, cada envio externo
 * segura um processo do PHP (timeout de ate 25 s) e, com muitos usuarios,
 * a fila de processos esgota e o sistema inteiro fica lento.
 *
 * Em console (artisan/testes) executa imediatamente, salvo se
 * `chat.defer_broadcast` forcar o comportamento web.
 */
class AfterResponse
{
    public static function run(\Closure $callback): void
    {
        $defer = config('chat.defer_broadcast');
        $defer = $defer === null ? ! app()->runningInConsole() : (bool) $defer;

        if (! $defer) {
            $callback();

            return;
        }

        app()->terminating(function () use ($callback) {
            try {
                $callback();
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }
}
