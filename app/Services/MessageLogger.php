<?php

namespace App\Services;

use App\Models\MessageLog;

class MessageLogger
{
    /**
     * Registra uma tentativa de envio. Nunca lança: um problema no log não pode
     * derrubar o envio da mensagem nem a requisição do usuário.
     *
     * @param  array{ok?: bool, error?: string}  $result retorno do serviço de envio
     * @param  array{origem?: string, protocol_id?: int, user_id?: int}  $meta
     */
    public static function log(string $canal, string $destino, ?string $assunto, string $mensagem, array $result, array $meta = []): void
    {
        try {
            $ok = (bool) ($result['ok'] ?? false);

            MessageLog::create([
                'canal' => $canal,
                'user_id' => $meta['user_id'] ?? null,
                'destino' => mb_substr($destino, 0, 190),
                'assunto' => $assunto !== null ? mb_substr($assunto, 0, 255) : null,
                'mensagem' => $mensagem,
                'status' => $ok ? 'enviado' : 'erro',
                'erro' => $ok ? null : ($result['error'] ?? 'Erro ao enviar mensagem.'),
                'origem' => $meta['origem'] ?? null,
                'protocol_id' => $meta['protocol_id'] ?? null,
                'enviada_em' => $ok ? now() : null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
