<?php

namespace App\Services\Secretarias;

/** Secretaria ativa da requisição (singleton), preenchida por ResolveActiveSecretaria. */
class ActiveSecretaria
{
    private ?int $id = null;

    private bool $all = false;

    /** @var int[] */
    private array $allowed = [];

    /** @param int[] $allowed */
    public function set(?int $id, bool $all, array $allowed): void
    {
        $this->id = $id;
        $this->all = $all;
        $this->allowed = array_values(array_map('intval', $allowed));
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function isAll(): bool
    {
        return $this->all;
    }

    /** @return int[] */
    public function allowedIds(): array
    {
        return $this->allowed;
    }
}
