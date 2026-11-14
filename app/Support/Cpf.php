<?php

namespace App\Support;

class Cpf
{
    /** Só os dígitos do CPF, qualquer que seja a formatação. */
    public static function digits(string $valor): string
    {
        return preg_replace('/\D/', '', $valor) ?? '';
    }
}
