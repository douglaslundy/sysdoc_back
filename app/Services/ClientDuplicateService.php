<?php

namespace App\Services;

use App\Models\Addresses;
use App\Models\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientDuplicateService
{
    private const TYPES = ['all', 'cpf', 'cns'];

    private const CNS_PLACEHOLDERS = [
        '000000000000000',
        '111111111111111',
        '222222222222222',
        '333333333333333',
        '444444444444444',
        '555555555555555',
        '666666666666666',
        '777777777777777',
        '888888888888888',
        '999999999999999',
        '.',
        '..',
        '...',
        '....',
        '.....',
    ];

    public function listDuplicateGroups(string $type = 'all'): array
    {
        $type = in_array($type, self::TYPES, true) ? $type : 'all';
        $groups = [];

        if ($type !== 'cns') {
            $groups = array_merge($groups, $this->buildGroupsForField('cpf'));
        }

        if ($type !== 'cpf') {
            $groups = array_merge($groups, $this->buildGroupsForField('cns'));
        }

        usort($groups, function (array $left, array $right): int {
            return [$left['identifier_type'], $left['identifier_value']]
                <=> [$right['identifier_type'], $right['identifier_value']];
        });

        return $groups;
    }

    public function summarize(array $groups): array
    {
        $candidateIds = [];

        foreach ($groups as $group) {
            foreach ($group['deletable_candidates'] as $candidate) {
                $candidateIds[$candidate['id']] = true;
            }
        }

        return [
            'groups' => count($groups),
            'deletable_candidates' => count($candidateIds),
        ];
    }

    public function deleteCandidates(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $candidateIndex = $this->candidateIndex();
        $invalidIds = [];

        foreach ($ids as $id) {
            if (! isset($candidateIndex[$id])) {
                $invalidIds[] = $id;
            }
        }

        if ($invalidIds !== []) {
            return [
                'deleted_ids' => [],
                'deleted_count' => 0,
                'invalid_ids' => array_values(array_unique($invalidIds)),
            ];
        }

        DB::transaction(function () use ($ids): void {
            Addresses::query()->whereIn('id_client', $ids)->delete();
            Client::query()->whereIn('id', $ids)->delete();
        });

        return [
            'deleted_ids' => $ids,
            'deleted_count' => count($ids),
            'invalid_ids' => [],
        ];
    }

    private function candidateIndex(): array
    {
        $groups = $this->listDuplicateGroups('all');
        $keeperIds = [];
        $candidates = [];

        foreach ($groups as $group) {
            $keeperIds[$group['keeper']['id']] = true;
        }

        foreach ($groups as $group) {
            foreach ($group['deletable_candidates'] as $candidate) {
                if (! isset($keeperIds[$candidate['id']])) {
                    $candidates[$candidate['id']] = $candidate;
                }
            }
        }

        return $candidates;
    }

    private function buildGroupsForField(string $field): array
    {
        return $this->duplicateRowsByField($field)
            ->groupBy($field)
            ->map(function (Collection $rows, string $identifierValue) use ($field) {
                $records = $rows
                    ->map(fn ($row) => $this->mapRow($row))
                    ->values()
                    ->all();

                $keeperId = $this->chooseKeeperId($records);
                $keeper = collect($records)->firstWhere('id', $keeperId);
                $deletableCandidates = collect($records)
                    ->filter(fn (array $record) => $record['id'] !== $keeperId && $record['can_delete'])
                    ->values()
                    ->all();

                if ($keeper === null || $deletableCandidates === []) {
                    return null;
                }

                $blockedRecords = collect($records)
                    ->filter(fn (array $record) => $record['id'] !== $keeperId && ! $record['can_delete'])
                    ->values()
                    ->all();

                return [
                    'identifier_type' => $field,
                    'identifier_value' => $identifierValue,
                    'total_records' => count($records),
                    'keeper' => $keeper,
                    'deletable_candidates' => $deletableCandidates,
                    'blocked_records' => $blockedRecords,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function duplicateRowsByField(string $field): Collection
    {
        $query = DB::table('clients as c')
            ->select([
                'c.id',
                'c.name',
                'c.cpf',
                'c.cns',
                'c.mother',
                'c.father',
                'c.phone',
                'c.email',
                'c.born_date',
                'c.sexo',
                'c.obs',
                'c.active',
            ])
            ->selectSub('SELECT COUNT(*) FROM queue q WHERE q.id_client = c.id', 'queue_count')
            ->selectSub('SELECT COUNT(*) FROM trip_clients tc WHERE tc.client_id = c.id', 'trip_count')
            ->selectSub('SELECT COUNT(*) FROM attendance_calls ac WHERE ac.client_id = c.id', 'attendance_call_count')
            ->selectSub('SELECT COUNT(*) FROM attendance_records ar WHERE ar.client_id = c.id', 'attendance_record_count')
            ->selectSub('SELECT COUNT(*) FROM attendance_tickets at WHERE at.client_id = c.id', 'attendance_ticket_count')
            ->selectSub('SELECT COUNT(*) FROM pedidos_exame pe WHERE pe.client_id = c.id', 'pedido_exame_count')
            ->whereNotNull("c.{$field}")
            ->whereRaw("TRIM(c.{$field}) <> ''")
            ->whereIn("c.{$field}", function ($subQuery) use ($field) {
                $subQuery
                    ->from('clients')
                    ->select($field)
                    ->whereNotNull($field)
                    ->whereRaw("TRIM({$field}) <> ''")
                    ->groupBy($field)
                    ->havingRaw('COUNT(*) > 1');

                if ($field === 'cns') {
                    $subQuery->whereNotIn($field, self::CNS_PLACEHOLDERS);
                }
            });

        if ($field === 'cns') {
            $query->whereNotIn('c.cns', self::CNS_PLACEHOLDERS);
        }

        return $query
            ->orderBy("c.{$field}")
            ->orderBy('c.id')
            ->get();
    }

    private function mapRow(object $row): array
    {
        $references = [
            'queue' => (int) $row->queue_count,
            'trip' => (int) $row->trip_count,
            'attendance_calls' => (int) $row->attendance_call_count,
            'attendance_records' => (int) $row->attendance_record_count,
            'attendance_tickets' => (int) $row->attendance_ticket_count,
            'pedidos_exame' => (int) $row->pedido_exame_count,
        ];

        $hasLinks = array_sum($references) > 0;

        return [
            'id' => (int) $row->id,
            'name' => $row->name,
            'cpf' => $row->cpf,
            'cns' => $row->cns,
            'active' => (bool) $row->active,
            'references' => $references,
            'has_links' => $hasLinks,
            'can_delete' => ! $hasLinks,
            'completeness_score' => $this->completenessScore((array) $row),
        ];
    }

    private function completenessScore(array $row): int
    {
        $fields = ['name', 'mother', 'father', 'cpf', 'cns', 'phone', 'email', 'born_date', 'sexo', 'obs'];
        $score = 0;

        foreach ($fields as $field) {
            $value = $row[$field] ?? null;

            if ($value !== null && trim((string) $value) !== '') {
                $score++;
            }
        }

        return $score;
    }

    private function chooseKeeperId(array $records): int
    {
        usort($records, function (array $left, array $right): int {
            $leftLinks = $left['has_links'] ? 1 : 0;
            $rightLinks = $right['has_links'] ? 1 : 0;

            if ($leftLinks !== $rightLinks) {
                return $rightLinks <=> $leftLinks;
            }

            $leftActive = $left['active'] ? 1 : 0;
            $rightActive = $right['active'] ? 1 : 0;

            if ($leftActive !== $rightActive) {
                return $rightActive <=> $leftActive;
            }

            if ($left['completeness_score'] !== $right['completeness_score']) {
                return $right['completeness_score'] <=> $left['completeness_score'];
            }

            return $left['id'] <=> $right['id'];
        });

        return (int) $records[0]['id'];
    }
}
