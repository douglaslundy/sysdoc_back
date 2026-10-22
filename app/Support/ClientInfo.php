<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Impressão digital técnica de quem fez a requisição: navegador, sistema, tipo/modelo do
 * aparelho, robô/ferramenta, idioma, cadeia de proxies e dados informados pelo próprio
 * navegador (cabeçalho X-Client-Info: fuso, tela, modelo via Client Hints etc.).
 *
 * Tudo que vem de cabeçalho é declarado pelo cliente e pode ser falsificado: serve como
 * indício forense, não como prova. O endereço MAC não chega ao servidor numa requisição
 * HTTP (fica na rede local do cliente), portanto não é capturável aqui.
 */
class ClientInfo
{
    private const BOTS = '/bot|crawl|spider|slurp|scan|curl|wget|python|java\/|go-http|okhttp|axios|node-fetch|postman|insomnia|httpie|libwww|sqlmap|nikto|nmap|masscan|zgrab|nuclei|hydra|headless|phantom|selenium|puppeteer|playwright|monitor|uptime|facebookexternalhit|whatsapp|telegram/i';

    private const CLIENT_KEYS = ['tz', 'screen', 'viewport', 'dpr', 'lang', 'platform', 'platform_version', 'model', 'arch', 'bitness', 'brands', 'mem', 'cores', 'touch', 'conn'];

    public static function capture(?Request $request = null): array
    {
        $request ??= request();
        if (! $request) {
            return [];
        }

        $ua = (string) $request->userAgent();
        $parsed = self::parseUserAgent($ua);
        $reported = self::reported($request);

        // Client Hints (Sec-CH-UA-*) e o que o próprio navegador informou refinam o User-Agent.
        $model = $reported['model'] ?? trim($request->header('Sec-CH-UA-Model', ''), '" ') ?: null;
        if ($model) {
            $parsed['device_model'] = $model;
        }
        if (! empty($reported['platform_version']) && $parsed['os'] === 'Windows') {
            // UA congelado diz sempre "Windows 10"; Client Hints (>= 13) indica Windows 11.
            $parsed['os_version'] = (int) $reported['platform_version'] >= 13 ? '11' : '10';
        }

        return array_filter([
            'browser' => $parsed['browser'],
            'browser_version' => $parsed['browser_version'],
            'os' => $parsed['os'],
            'os_version' => $parsed['os_version'],
            'device_type' => $parsed['device_type'],
            'device_brand' => $parsed['device_brand'],
            'device_model' => $parsed['device_model'],
            'is_bot' => $parsed['is_bot'],
            'user_agent' => mb_substr($ua, 0, 500),
            'language' => mb_substr((string) $request->header('Accept-Language', ''), 0, 100),
            'referer' => mb_substr((string) $request->headers->get('referer', ''), 0, 255),
            'origin' => mb_substr((string) $request->header('Origin', ''), 0, 255),
            'forwarded_for' => mb_substr((string) $request->header('X-Forwarded-For', ''), 0, 255),
            'remote_addr' => $request->server('REMOTE_ADDR'),
            'host' => $request->getHost(),
            'protocol' => $request->server('SERVER_PROTOCOL'),
            'reported' => $reported,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }

    /** @return array<string, mixed> campos de X-Client-Info (JSON em URL-encode), só chaves conhecidas. */
    private static function reported(Request $request): array
    {
        $raw = (string) $request->header('X-Client-Info', '');
        if ($raw === '' || strlen($raw) > 2048) {
            return [];
        }

        $data = json_decode(rawurldecode($raw), true);
        if (! is_array($data)) {
            return [];
        }

        $out = [];
        foreach (self::CLIENT_KEYS as $key) {
            if (isset($data[$key]) && (is_scalar($data[$key]) || is_array($data[$key]))) {
                $out[$key] = is_scalar($data[$key]) ? mb_substr((string) $data[$key], 0, 120) : array_slice($data[$key], 0, 8);
            }
        }

        return $out;
    }

    public static function parseUserAgent(string $ua): array
    {
        $r = [
            'browser' => null, 'browser_version' => null, 'os' => null, 'os_version' => null,
            'device_type' => 'desktop', 'device_brand' => null, 'device_model' => null,
            'is_bot' => false,
        ];

        if ($ua === '') {
            return ['device_type' => 'desconhecido'] + $r;
        }

        if (preg_match(self::BOTS, $ua)) {
            $r['is_bot'] = true;
            $r['device_type'] = 'robô/ferramenta';
        }

        // Navegador (ordem importa: Edge/Opera/Samsung também dizem "Chrome").
        $browsers = [
            'Edge' => '/Edg(?:e|A|iOS)?\/([\d.]+)/', 'Opera' => '/(?:OPR|Opera)\/([\d.]+)/',
            'Samsung Internet' => '/SamsungBrowser\/([\d.]+)/', 'Firefox' => '/(?:Firefox|FxiOS)\/([\d.]+)/',
            'Chrome' => '/(?:Chrome|CriOS)\/([\d.]+)/', 'Safari' => '/Version\/([\d.]+).*Safari/',
            'Internet Explorer' => '/(?:MSIE |Trident\/.*rv:)([\d.]+)/',
            'curl' => '/curl\/([\d.]+)/', 'Python requests' => '/python-requests\/([\d.]+)/',
            'Postman' => '/PostmanRuntime\/([\d.]+)/', 'Go http' => '/Go-http-client\/([\d.]+)/',
        ];
        foreach ($browsers as $name => $pattern) {
            if (preg_match($pattern, $ua, $m)) {
                $r['browser'] = $name;
                $r['browser_version'] = $m[1];
                break;
            }
        }

        // Sistema operacional e aparelho.
        if (preg_match('/Android ([\d.]+)/', $ua, $m)) {
            $r['os'] = 'Android';
            $r['os_version'] = $m[1];
            $r['device_type'] = stripos($ua, 'Mobile') !== false ? 'celular' : 'tablet';
            if (preg_match('/Android [\d.]+; ([^;)]+?)(?: Build|\)|;)/', $ua, $mm) && $mm[1] !== 'K') {
                $r['device_model'] = trim($mm[1]);
                $r['device_brand'] = self::brandFromModel($r['device_model']);
            }
        } elseif (preg_match('/\((iPhone|iPad|iPod)[^)]*OS ([\d_]+)/', $ua, $m)) {
            $r['os'] = 'iOS';
            $r['os_version'] = str_replace('_', '.', $m[2]);
            $r['device_type'] = $m[1] === 'iPad' ? 'tablet' : 'celular';
            $r['device_brand'] = 'Apple';
            $r['device_model'] = $m[1];
        } elseif (preg_match('/Windows NT ([\d.]+)/', $ua, $m)) {
            $r['os'] = 'Windows';
            $r['os_version'] = ['10.0' => '10', '6.3' => '8.1', '6.2' => '8', '6.1' => '7'][$m[1]] ?? $m[1];
        } elseif (preg_match('/Mac OS X ([\d_.]+)/', $ua, $m)) {
            $r['os'] = 'macOS';
            $r['os_version'] = str_replace('_', '.', $m[1]);
            $r['device_brand'] = 'Apple';
            $r['device_model'] = 'Mac';
        } elseif (stripos($ua, 'CrOS') !== false) {
            $r['os'] = 'ChromeOS';
        } elseif (stripos($ua, 'Linux') !== false) {
            $r['os'] = 'Linux';
        }

        if ($r['is_bot']) {
            $r['device_type'] = 'robô/ferramenta';
        }

        return $r;
    }

    private static function brandFromModel(string $model): ?string
    {
        $brands = [
            '/^(SM-|GT-|SAMSUNG)/i' => 'Samsung', '/^Pixel/i' => 'Google', '/^(Redmi|Mi |M\d{4}|POCO|2\d{6}[A-Z]|22\d{6})/i' => 'Xiaomi',
            '/^(moto|XT\d)/i' => 'Motorola', '/^(LM-|LG)/i' => 'LG', '/^(CPH|RMX)/i' => 'Oppo/Realme',
            '/^(Infinix|TECNO|itel)/i' => 'Transsion', '/^(ASUS|ZenFone|I00)/i' => 'Asus', '/^(HUAWEI|HONOR|[A-Z]{3}-L\d)/i' => 'Huawei/Honor',
        ];
        foreach ($brands as $pattern => $brand) {
            if (preg_match($pattern, $model)) {
                return $brand;
            }
        }

        return null;
    }
}
