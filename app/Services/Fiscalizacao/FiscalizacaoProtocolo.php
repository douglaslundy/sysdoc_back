<?php

namespace App\Services\Fiscalizacao;

final class FiscalizacaoProtocolo
{
    /** Número de protocolo: FIS-AAAA-NNNNNN (ano de criação + id com 6 dígitos). */
    public static function for(int $id, \DateTimeInterface $createdAt): string
    {
        return sprintf('FIS-%s-%06d', $createdAt->format('Y'), $id);
    }
}
