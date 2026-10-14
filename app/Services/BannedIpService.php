<?php

namespace App\Services;

use App\Models\BannedIp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Banimento de IPs de scanners/robôs que testam rotas inexistentes.
 * Só conta 404 de requisições SEM token (usuário logado nunca acumula strikes).
 */
class BannedIpService
{
    public const STRIKE_LIMIT = 15;      // 404s no intervalo abaixo => ban
    public const STRIKE_WINDOW = 600;    // segundos
    public const AUTO_BAN_DAYS = 30;
    private const CACHE_TTL = 300;

    public function isBanned(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        try {
            return Cache::remember('banned_ip:'.$ip, self::CACHE_TTL, function () use ($ip) {
                return BannedIp::where('ip', $ip)
                    ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                    ->exists();
            });
        } catch (Throwable $e) {
            // Falha aberta: problema no banco/cache não pode derrubar o sistema.
            return false;
        }
    }

    public function ban(string $ip, string $reason, string $source = 'manual', ?int $days = null, int $hits = 0): BannedIp
    {
        $banned = BannedIp::updateOrCreate(
            ['ip' => $ip],
            [
                'reason' => mb_substr($reason, 0, 255),
                'source' => $source,
                'hits' => $hits,
                'expires_at' => $days ? now()->addDays($days) : null,
            ]
        );
        Cache::forget('banned_ip:'.$ip);

        return $banned;
    }

    public function unban(string $ip): void
    {
        BannedIp::where('ip', $ip)->delete();
        Cache::forget('banned_ip:'.$ip);
        Cache::forget('scan404:'.$ip);
    }

    /** Registra um 404 anônimo; bane ao atingir o limite. */
    public function registerNotFound(?string $ip, string $path): void
    {
        if (! $ip || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return; // nunca bane loopback/rede interna
        }

        try {
            $key = 'scan404:'.$ip;
            Cache::add($key, 0, self::STRIKE_WINDOW);
            $count = Cache::increment($key);

            if ($count >= self::STRIKE_LIMIT && ! $this->isBanned($ip)) {
                $this->ban($ip, "Varredura de rotas inexistentes ({$count} x 404 em ".(self::STRIKE_WINDOW / 60)." min; última: /{$path})", 'auto', self::AUTO_BAN_DAYS, $count);
                Log::warning('IP banido por varredura de rotas', ['ip' => $ip, 'hits' => $count]);
            }
        } catch (Throwable $e) {
            // Contagem é best-effort.
        }
    }
}
