<?php

namespace App\Services\Secretarias;

/** Secretaria ativa da requisição (singleton), preenchida por ResolveActiveSecretaria. */
class ActiveSecretaria
{
    private ?int $id = null;

    private bool $all = false;

    /** @var int[] */
    private array $allowed = [];

    private bool $resolved = false;

    /** @param int[] $allowed */
    public function set(?int $id, bool $all, array $allowed): void
    {
        $this->resolved = true;
        $this->id = $id;
        $this->all = $all;
        $this->allowed = array_values(array_map('intval', $allowed));
    }

    /** false = nenhuma requisição autenticada definiu a secretaria (console, fila, seeder). */
    public function resolved(): bool
    {
        return $this->resolved;
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
