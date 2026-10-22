<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/** Localização/provedor de um IP público via ipwho.is, com cache (best-effort: null se falhar). */
class IpOriginService
{
    private const TTL = 2592000; // 30 dias: a localização de um IP muda raramente

    public function isPublic(?string $ip): bool
    {
        return (bool) $ip && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    /** Só o que já está em cache (sem rede): para caminhos que não podem esperar, como o log de erro. */
    public function cached(?string $ip): ?string
    {
        return $this->isPublic($ip) ? Cache::get('ip-origin:'.$ip) : null;
    }

    public function lookup(?string $ip): ?string
    {
        if (! $this->isPublic($ip)) {
            return null;
        }

        try {
            return Cache::remember('ip-origin:'.$ip, self::TTL, fn () => $this->fetch($ip));
        } catch (Throwable $e) {
            return null;
        }
    }

    private function fetch(string $ip): ?string
    {
        if (Cache::has('ipgeo_down')) {
            throw new \RuntimeException('serviço de geolocalização em pausa');
        }

        try {
            $data = Http::timeout(2)->get('https://ipwho.is/'.$ip)->json();
        } catch (Throwable $e) {
            Cache::put('ipgeo_down', true, 300); // não trava a tela se o serviço cair

            throw $e;
        }

        if (! ($data['success'] ?? false)) {
            throw new \RuntimeException('IP sem resposta'); // não guarda resultado vazio em cache
        }

        $place = collect([$data['city'] ?? null, $data['region'] ?? null, $data['country'] ?? null])->filter()->implode(', ');
        $provider = $data['connection']['isp'] ?? $data['connection']['org'] ?? null;

        return mb_substr(trim($place.($provider ? ' — '.$provider : '')), 0, 255) ?: null;
    }
}
