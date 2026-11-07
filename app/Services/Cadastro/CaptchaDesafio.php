<?php

namespace App\Services\Cadastro;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Throwable;

/**
 * Captcha aritmético sem estado no servidor: o token carrega (cifrado) resposta, validade e nonce.
 * O nonce é gasto no Cache padrão (vale entre workers) na PRIMEIRA tentativa, certa ou errada,
 * para que um token não permita testar as ~18 respostas possíveis.
 */
class CaptchaDesafio
{
    private const TTL = 300;

    private const MAX_TOKEN = 2000;

    public function gerar(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $soma = random_int(0, 1) === 1;
        if (! $soma && $a < $b) {
            [$a, $b] = [$b, $a];
        }
        $resposta = $soma ? $a + $b : $a - $b;

        $token = Crypt::encryptString(json_encode([
            'resposta' => $resposta,
            'exp' => now()->timestamp + self::TTL,
            'nonce' => Str::random(32),
        ]));

        return ['pergunta' => 'Quanto é '.$a.($soma ? ' + ' : ' - ').$b.'?', 'token' => $token];
    }

    public function validar(string $token, string $resposta): bool
    {
        if ($token === '' || strlen($token) > self::MAX_TOKEN) {
            return false;
        }

        try {
            $dados = json_decode(Crypt::decryptString($token), true);
        } catch (Throwable) {
            return false;
        }

        if (! is_array($dados) || ! is_int($dados['resposta'] ?? null) || ! is_int($dados['exp'] ?? null)
            || ! is_string($dados['nonce'] ?? null) || $dados['nonce'] === '') {
            return false;
        }

        $restante = $dados['exp'] - now()->timestamp;
        if ($restante <= 0) {
            return false;
        }

        // add() é atômico: só uma validação concorrente consegue gastar o nonce.
        if (! Cache::add('captcha:nonce:'.$dados['nonce'], 1, $restante)) {
            return false;
        }

        $resposta = trim($resposta);

        return preg_match('/^\d{1,3}$/', $resposta) === 1 && (int) $resposta === $dados['resposta'];
    }
}
