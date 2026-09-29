<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\Audit\AuditContext;
use App\Support\AfterResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AuditService
{
    private static array $sensitive = [
        'password', 'remember_token', 'token', 'secret', 'api_key', 'apikey', 'authorization',
        'smtp_password', 'app_secret', 'whatsapp_api_key',
    ];

    private static array $buffer = [];

    /** Aplicacao para a qual o flush ja foi agendado (nao vaza entre requisicoes/testes/workers). */
    private static ?\WeakReference $scheduledFor = null;

    /**
     * Registra a auditoria no maximo UMA vez por visitante (IP) e por acao dentro da
     * janela informada. Para paginas publicas consultadas o dia inteiro (paineis de TV
     * fazem polling continuo), gravar cada requisicao enche a tabela de logs sem valor.
     * O visitante segue rastreavel (IP no registro), mas 1 linha por hora em vez de milhares.
     */
    public static function recordOncePerVisitor(
        string $action,
        ?array $new = null,
        int $windowSeconds = 3600
    ): void {
        $ip = request()->ip() ?? 'cli';

        if (! Cache::add('audit-once:'.$action.':'.sha1($ip), 1, $windowSeconds)) {
            return;
        }

        self::record($action, null, null, $new);
    }

    /**
     * Registra uma acao de auditoria. A linha vai para um buffer em memoria e todas as
     * linhas da requisicao sao gravadas em UM unico INSERT depois da resposta
     * (em console/testes grava na hora). Falha de auditoria nunca derruba a requisicao.
     */
    public static function record(
        string $action,
        ?Model $model = null,
        ?array $old = null,
        ?array $new = null,
        ?User $actingUser = null,
        ?int $clientId = null
    ): void {
        try {
            $user = $actingUser ?? Auth::user();

            self::$buffer[] = [
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Sistema',
                'action' => $action,
                'model_type' => $model ? class_basename($model) : null,
                'model_id' => $model?->getKey(),
                'client_id' => $clientId ?? AuditContext::clientIdFor($model),
                'endpoint' => request()->path(),
                'method' => request()->method(),
                'ip_address' => request()->ip() ?? '0.0.0.0',
                'user_agent' => substr(request()->userAgent() ?? '', 0, 255),
                'old_values' => $old ? json_encode(self::sanitize($old)) : null,
                'new_values' => $new ? json_encode(self::sanitize($new)) : null,
                'created_at' => now(),
            ];

            if (self::$scheduledFor?->get() !== app()) {
                self::$scheduledFor = \WeakReference::create(app());
                AfterResponse::run(fn () => self::flush());
            }
        } catch (\Throwable $e) {
            // Auditoria não pode quebrar a aplicação, mas a falha precisa ser rastreável.
            Log::error('Falha ao gravar auditoria.', [
                'action' => $action,
                'model_type' => $model ? class_basename($model) : null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /** Grava o buffer em lote. Chamado ao fim da requisicao; seguro chamar varias vezes. */
    public static function flush(): void
    {
        $rows = self::$buffer;
        self::$buffer = [];
        self::$scheduledFor = null;

        if ($rows === []) {
            return;
        }

        try {
            AuditLog::insert($rows);
        } catch (\Throwable $e) {
            Log::error('Falha ao gravar auditoria em lote.', ['linhas' => count($rows), 'error' => $e->getMessage()]);
        }
    }

    /**
     * Visualizacao de um registro: no maximo 1 linha por usuario e registro dentro da
     * janela (padrao 10 min), para nao encher a tabela quando a mesma pessoa reabre a tela.
     */
    public static function recordViewOncePer(string $action, Model $model, ?array $new = null, int $seconds = 600): void
    {
        $key = 'audit-view:'.(Auth::id() ?? 'anon').':'.$action.':'.class_basename($model).':'.$model->getKey();

        if (Cache::add($key, 1, $seconds)) {
            self::record($action, $model, null, $new);
        }
    }

    /** Mascara segredos (em qualquer nivel) em vez de descarta-los: fica registrado QUE mudaram. */
    private static function sanitize(array $data): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::$sensitive, true)) {
                $clean[$key] = '[mascarado]';

                continue;
            }
            $clean[$key] = is_array($value) ? self::sanitize($value) : $value;
        }

        return $clean;
    }
}
