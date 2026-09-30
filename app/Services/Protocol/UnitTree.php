<?php

namespace App\Services\Protocol;

use App\Models\ProtocolOrganizationalUnit;
use App\Models\ProtocolUserUnit;

/** Consultas à árvore de unidades organizacionais (secretaria › departamento › setor). */
class UnitTree
{
    /** @return array<int, int> a unidade e todas as suas subunidades (qualquer nível). */
    public static function withDescendantIds(int $unitId): array
    {
        $ids = [$unitId];
        $frontier = [$unitId];

        while ($frontier !== []) {
            $frontier = ProtocolOrganizationalUnit::query()->whereIn('parent_id', $frontier)->pluck('id')->all();
            $ids = array_merge($ids, $frontier);
        }

        return $ids;
    }

    /**
     * Unidades "do usuário" para fins de visibilidade: aquelas em que ele está lotado (ativo) e
     * todas as unidades acima delas. Quem é do setor enxerga o que é da sua unidade de cima.
     *
     * @return array<int, int>
     */
    public static function visibleUnitIdsForUser(int $userId): array
    {
        $current = ProtocolUserUnit::query()
            ->where('user_id', $userId)
            ->where('ativo', true)
            ->pluck('protocol_organizational_unit_id')
            ->all();
        $ids = $current;

        while ($current !== []) {
            $current = ProtocolOrganizationalUnit::query()
                ->whereIn('id', $current)
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->diff($ids)
                ->values()
                ->all();
            $ids = array_merge($ids, $current);
        }

        return array_values(array_unique($ids));
    }
}
