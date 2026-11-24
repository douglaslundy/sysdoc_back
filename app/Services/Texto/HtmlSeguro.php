<?php

namespace App\Services\Texto;

use HTMLPurifier;
use HTMLPurifier_Config;

/**
 * Limpa o HTML dos textos longos editados no editor rico (TipTap): só a formatação da lista abaixo passa.
 * Texto puro (registros antigos) volta intacto; HTML sem texto vira null.
 */
class HtmlSeguro
{
    private const PERMITIDOS = 'p[style],br,strong,em,u,s,h2[style],h3[style],ul,ol,li,blockquote,a[href|target|rel],img[src|alt],span[style]';

    private static ?HTMLPurifier $purifier = null;

    public static function limpar(?string $valor): ?string
    {
        if ($valor === null || trim($valor) === '') {
            return null;
        }
        if (! self::temHtml($valor)) {
            return $valor;
        }

        $limpo = trim(self::purifier()->purify($valor));
        if (self::texto($limpo) === '' && ! str_contains($limpo, '<img')) {
            return null;
        }

        return $limpo;
    }

    /** Texto sem tags, com entidades decodificadas e espaços colapsados (para contar e para prévias). */
    public static function texto(?string $valor): string
    {
        if ($valor === null) {
            return '';
        }
        $semTags = html_entity_decode(preg_replace('/<[^>]*>/', ' ', $valor), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[\s\x{00A0}]+/u', ' ', $semTags));
    }

    public static function temHtml(string $valor): bool
    {
        return (bool) preg_match('/<[a-z][\s\S]*>/i', $valor);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier) {
            return self::$purifier;
        }

        $cache = storage_path('framework/cache/htmlpurifier');
        if (! is_dir($cache)) {
            @mkdir($cache, 0775, true);
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $config->set('Cache.SerializerPath', is_writable($cache) ? $cache : null);
        $config->set('HTML.Allowed', self::PERMITIDOS);
        $config->set('CSS.AllowedProperties', ['font-size', 'text-align', 'color']);
        // data: fica de fora: imagem colada em base64 nunca é gravada.
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('Attr.AllowedRel', ['noopener', 'noreferrer', 'nofollow']);
        $config->set('HTML.TargetNoopener', true);
        $config->set('HTML.TargetNoreferrer', true);

        return self::$purifier = new HTMLPurifier($config);
    }
}
