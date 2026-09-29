<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * O Sanctum grava `last_used_at` em TODA requisicao autenticada (um UPDATE por
 * chamada). Com centenas de usuarios e varias chamadas por segundo isso vira a
 * escrita mais frequente do banco. Aqui a gravacao so acontece se o valor
 * anterior tiver mais de LAST_USED_RESOLUTION_MINUTES minutos.
 */
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    public const LAST_USED_RESOLUTION_MINUTES = 5;

    public function save(array $options = [])
    {
        if ($this->exists && array_keys($this->getDirty()) === ['last_used_at']) {
            $previous = $this->getOriginal('last_used_at');

            if ($previous && $previous->gt(now()->subMinutes(self::LAST_USED_RESOLUTION_MINUTES))) {
                $this->syncOriginal();

                return true;
            }
        }

        return parent::save($options);
    }
}
