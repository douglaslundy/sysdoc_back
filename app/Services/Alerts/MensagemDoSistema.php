<?php

namespace App\Services\Alerts;

use App\Models\ProtocolAlert;
use Throwable;

/**
 * Texto das mensagens enviadas por código próprio (OS, cadastro público, gestor, protocolo por WhatsApp,
 * vigilância). O texto mora na tabela protocol_alerts (linhas `sistema`) e é editado na tela Alertas;
 * config/alert_catalog.php é a semente e o reserva: se a linha ou a coluna ainda não existir (código novo
 * antes da migration), o aviso sai com o texto padrão em vez de deixar de ser enviado.
 */
class MensagemDoSistema
{
    /**
     * @param  array<string, scalar|null>  $variaveis  nome => valor (sem as chaves)
     * @return array{assunto: ?string, texto: string}|null  null = mensagem desativada na tela Alertas
     */
    public static function montar(string $modulo, string $gatilho, array $variaveis = []): ?array
    {
        $padrao = config("alert_catalog.sistema.{$modulo}:{$gatilho}");
        $linha = self::linha($modulo, $gatilho);

        if ($linha && ! $linha->ativo) {
            return null;
        }

        $template = trim((string) $linha?->template) !== '' ? (string) $linha->template : (string) ($padrao['template'] ?? '');
        $assunto = trim((string) $linha?->assunto) !== '' ? (string) $linha->assunto : ($padrao['assunto'] ?? null);

        if ($template === '') {
            return null;
        }

        return [
            'assunto' => $assunto === null ? null : self::preencher($assunto, $variaveis),
            'texto' => self::preencher($template, $variaveis),
        ];
    }

    public static function preencher(string $texto, array $variaveis): string
    {
        $busca = [];
        $troca = [];
        foreach ($variaveis as $nome => $valor) {
            $busca[] = '{{'.$nome.'}}';
            $troca[] = (string) ($valor ?? '');
        }
        $texto = str_replace($busca, $troca, $texto);

        // Variável desconhecida (erro de digitação na edição) some em vez de aparecer crua para o usuário.
        return trim((string) preg_replace('/{{\s*[a-z_0-9]+\s*}}/i', '', $texto));
    }

    private static function linha(string $modulo, string $gatilho): ?ProtocolAlert
    {
        try {
            return ProtocolAlert::query()->where('sistema', true)->where('modulo', $modulo)->where('gatilho', $gatilho)->first();
        } catch (Throwable $e) {
            return null; // coluna/tabela ainda não migrada: vale o texto padrão.
        }
    }
}
