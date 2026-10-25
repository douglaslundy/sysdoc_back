<?php

namespace App\Services\Iluminacao;

final class IluminacaoProtocolo
{
    /** Número de protocolo: IP-AAAA-NNNNNN (ano de criação + id com 6 dígitos). */
    public static function for(int $id, \DateTimeInterface $createdAt): string
    {
        return sprintf('IP-%s-%06d', $createdAt->format('Y'), $id);
    }
}
