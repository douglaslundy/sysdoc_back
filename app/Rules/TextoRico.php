<?php

namespace App\Rules;

use App\Services\Texto\HtmlSeguro;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Tamanho de texto rico: conta o texto sem tags; o HTML inteiro tem um teto próprio (cabe na coluna). */
class TextoRico implements ValidationRule
{
    public function __construct(private int $maxTexto, private int $minTexto = 0, private int $maxHtml = 60000) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }
        if (mb_strlen($value) > $this->maxHtml) {
            $fail('O texto formatado ficou grande demais. Reduza o texto ou a formatação.');

            return;
        }
        $tamanho = mb_strlen(HtmlSeguro::texto($value));
        if ($tamanho > $this->maxTexto) {
            $fail("O texto deve ter no máximo {$this->maxTexto} caracteres.");
        } elseif ($tamanho < $this->minTexto) {
            $fail("O texto deve ter no mínimo {$this->minTexto} caracteres.");
        }
    }
}
