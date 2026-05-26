<?php

namespace App\Services\Pharmacy;

use App\Models\MedicineDailyStatus;
use App\Models\MedicineItem;
use App\Services\AuditService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicineStockImportService
{
    private const MAX_AVAILABLE_QUANTITY = 9999999999.99;
    private const FORM_SYNONYMS = [
        'COMPRIMIDO REVESTIDO' => 'COMPRIMIDO',
        'COMPRIMIDO MASTIGAVEL' => 'COMPRIMIDO',
        'COMPRIMIDO SUBLINGUAL' => 'COMPRIMIDO',
        'COMPRIMIDO' => 'COMPRIMIDO',
        'CAPSULA DURA' => 'CAPSULA',
        'CAPSULA MOLE' => 'CAPSULA',
        'CAPSULA INALANTE' => 'CAPSULA INALANTE',
        'CAPSULA' => 'CAPSULA',
        'PO PARA SUSPENSAO ORAL' => 'SUSPENSAO ORAL',
        'SUSPENSAO ORAL' => 'SUSPENSAO ORAL',
        'SOLUCAO ORAL' => 'SOLUCAO ORAL',
        'SOLUCAO INJETAVEL' => 'SOLUCAO INJETAVEL',
        'CREME DERMATOLOGICO' => 'CREME',
        'CREME' => 'CREME',
        'POMADA' => 'POMADA',
        'XAROPE' => 'XAROPE',
        'GEL' => 'GEL',
        'FRASCO AMPOLA' => 'FRASCO AMPOLA',
    ];

    public function import(UploadedFile $file, ?int $userId): array
    {
        $rows = $this->readCsvRows($file->getRealPath());
        if ($this->isBackupSnapshotCsv($rows)) {
            return $this->importBackupSnapshot($rows, $file, $userId);
        }

        $aggregated = $this->aggregateRows($rows);
        $medicines = MedicineItem::query()
            ->where('active', true)
            ->get(['id', 'active_ingredient', 'concentration', 'pharmaceutical_form']);

        $today = Carbon::today()->toDateString();
        $report = [
            'reference_date' => $today,
            'total_rows' => count($rows),
            'grouped_items' => count($aggregated),
            'updated_items' => 0,
            'imported_items' => [],
            'unmatched_items' => [],
            'ambiguous_items' => [],
        ];

        DB::transaction(function () use ($aggregated, $medicines, $today, $userId, &$report) {
            foreach ($aggregated as $item) {
                $match = $this->matchMedicine($item, $medicines);
                if ($match['status'] === 'unmatched') {
                    $report['unmatched_items'][] = [
                        'csv_name' => $item['produto_raw'],
                        'farmaco' => $item['farmaco_raw'],
                        'concentration' => $item['concentration_norm'] ?? null,
                        'quantity' => $item['quantity'],
                        'reason' => $match['reason'] ?? 'Sem correspondência confiável no cadastro de medicamentos.',
                    ];
                    continue;
                }

                if ($match['status'] === 'ambiguous') {
                    $report['ambiguous_items'][] = [
                        'csv_name' => $item['produto_raw'],
                        'farmaco' => $item['farmaco_raw'],
                        'concentration' => $item['concentration_norm'] ?? null,
                        'quantity' => $item['quantity'],
                        'candidates' => $match['candidates'],
                        'reason' => 'Mais de um medicamento no banco com mesma pontuação de conciliação.',
                    ];
                    continue;
                }

                $quantity = round((float) $item['quantity'], 2);
                $availabilityStatus = $quantity > 0 ? 'available' : 'unavailable';
                $medicineId = $match['medicine_id'];

                MedicineDailyStatus::updateOrCreate(
                    [
                        'medicine_item_id' => $medicineId,
                        'reference_date' => $today,
                    ],
                    [
                        'availability_status' => $availabilityStatus,
                        'available_quantity' => $quantity,
                        'updated_by_user_id' => $userId,
                    ]
                );

                $report['updated_items']++;
                $report['imported_items'][] = [
                    'medicine_item_id' => $medicineId,
                    'csv_name' => $item['produto_raw'],
                    'farmaco' => $item['farmaco_raw'],
                    'concentration' => $item['concentration_norm'] ?? null,
                    'quantity' => $quantity,
                    'availability_status' => $availabilityStatus,
                ];
            }
        });

        AuditService::record('IMPORT_STOCK_CSV', null, null, [
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $report['total_rows'],
            'grouped_items' => $report['grouped_items'],
            'updated_items' => $report['updated_items'],
            'unmatched_count' => count($report['unmatched_items']),
            'ambiguous_count' => count($report['ambiguous_items']),
        ]);

        return $report;
    }

    private function isBackupSnapshotCsv(array $rows): bool
    {
        if (empty($rows)) {
            return false;
        }

        $firstRow = $rows[0];

        return array_key_exists('medicine_item_id', $firstRow)
            && array_key_exists('available_quantity', $firstRow);
    }

    private function importBackupSnapshot(array $rows, UploadedFile $file, ?int $userId): array
    {
        $today = Carbon::today()->toDateString();
        $report = [
            'reference_date' => $today,
            'total_rows' => count($rows),
            'grouped_items' => count($rows),
            'updated_items' => 0,
            'imported_items' => [],
            'unmatched_items' => [],
            'ambiguous_items' => [],
        ];

        DB::transaction(function () use ($rows, $today, $userId, &$report) {
            $normalizedInternalCodeMap = MedicineItem::query()
                ->withTrashed()
                ->get(['id', 'internal_code'])
                ->mapWithKeys(function ($medicine) {
                    return [$this->normalizeInternalCode((string) $medicine->internal_code) => $medicine->id];
                })
                ->filter(function ($id, $code) {
                    return $code !== '';
                });

            foreach ($rows as $row) {
                $medicineId = (int) ($row['medicine_item_id'] ?? 0);
                $internalCode = trim((string) ($row['internal_code'] ?? ''));
                $normalizedInternalCode = $this->normalizeInternalCode($internalCode);

                $medicine = null;
                if ($medicineId > 0) {
                    $medicine = MedicineItem::query()->withTrashed()->find($medicineId);
                }
                if (! $medicine && $internalCode !== '') {
                    $medicine = MedicineItem::query()->withTrashed()->where('internal_code', $internalCode)->first();
                }
                if (! $medicine && $normalizedInternalCode !== '' && isset($normalizedInternalCodeMap[$normalizedInternalCode])) {
                    $medicine = MedicineItem::query()->withTrashed()->find((int) $normalizedInternalCodeMap[$normalizedInternalCode]);
                }
                if (! $medicine) {
                    $report['unmatched_items'][] = [
                        'csv_name' => $row['active_ingredient'] ?? null,
                        'farmaco' => $row['internal_code'] ?? null,
                        'concentration' => $row['concentration'] ?? null,
                        'quantity' => $row['available_quantity'] ?? null,
                        'medicine_item_id_lido' => $medicineId ?: null,
                        'internal_code_lido' => $internalCode !== '' ? $internalCode : null,
                        'reason' => 'CSV de backup sem medicine_item_id válido e internal_code não encontrado no banco.',
                    ];
                    continue;
                }

                $quantityRaw = (string) ($row['available_quantity'] ?? '0');
                $quantity = $this->parseDecimalSmart($quantityRaw);
                if ($quantity > self::MAX_AVAILABLE_QUANTITY) {
                    $report['unmatched_items'][] = [
                        'csv_name' => $row['active_ingredient'] ?? null,
                        'farmaco' => $row['internal_code'] ?? null,
                        'concentration' => $row['concentration'] ?? null,
                        'quantity' => $quantityRaw,
                        'medicine_item_id_lido' => $medicineId ?: null,
                        'internal_code_lido' => $internalCode !== '' ? $internalCode : null,
                        'reason' => 'Quantidade fora do limite permitido para o campo de estoque (máximo 9.999.999.999,99).',
                    ];
                    continue;
                }
                $statusFromFile = trim((string) ($row['availability_status'] ?? ''));
                $availabilityStatus = in_array($statusFromFile, ['available', 'unavailable'], true)
                    ? $statusFromFile
                    : ($quantity > 0 ? 'available' : 'unavailable');

                MedicineDailyStatus::updateOrCreate(
                    [
                        'medicine_item_id' => $medicine->id,
                        'reference_date' => $today,
                    ],
                    [
                        'availability_status' => $availabilityStatus,
                        'available_quantity' => round((float) $quantity, 2),
                        'updated_by_user_id' => $userId,
                    ]
                );

                $report['updated_items']++;
                $report['imported_items'][] = [
                    'medicine_item_id' => $medicine->id,
                    'csv_name' => $row['active_ingredient'] ?? null,
                    'farmaco' => $internalCode !== '' ? $internalCode : null,
                    'concentration' => $row['concentration'] ?? null,
                    'quantity' => round((float) $quantity, 2),
                    'availability_status' => $availabilityStatus,
                ];
            }
        });

        AuditService::record('IMPORT_STOCK_BACKUP_CSV', null, null, [
            'file_name' => $file->getClientOriginalName(),
            'total_rows' => $report['total_rows'],
            'updated_items' => $report['updated_items'],
            'unmatched_count' => count($report['unmatched_items']),
        ]);

        return $report;
    }

    public function currentStockSnapshot(): Collection
    {
        $latestSub = DB::table('medicine_daily_statuses as mds')
            ->select('mds.medicine_item_id', DB::raw('MAX(mds.id) as latest_status_id'))
            ->groupBy('mds.medicine_item_id');

        return DB::table('medicine_items as mi')
            ->leftJoinSub($latestSub, 'latest', function ($join) {
                $join->on('latest.medicine_item_id', '=', 'mi.id');
            })
            ->leftJoin('medicine_daily_statuses as ds', 'ds.id', '=', 'latest.latest_status_id')
            ->where('mi.active', true)
            ->whereNull('mi.deleted_at')
            ->orderBy('mi.active_ingredient')
            ->get([
                'mi.id as medicine_item_id',
                'mi.internal_code',
                'mi.active_ingredient',
                'mi.concentration',
                'mi.pharmaceutical_form',
                'mi.presentation',
                'ds.reference_date',
                'ds.availability_status',
                'ds.available_quantity',
            ]);
    }

    private function readCsvRows(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'rb');
        if (! $handle) {
            return $rows;
        }

        $headers = [];
        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if (empty($headers)) {
                $headers = array_map([$this, 'normalizeHeader'], $data);
                continue;
            }

            if (! is_array($data) || count(array_filter($data, fn ($value) => trim((string) $value) !== '')) === 0) {
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                $row[$header] = isset($data[$index]) ? $this->toUtf8(trim((string) $data[$index])) : null;
            }
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function aggregateRows(array $rows): array
    {
        $grouped = [];
        $seenRows = [];
        foreach ($rows as $row) {
            $rowFingerprint = $this->buildCsvRowFingerprint($row);
            if (isset($seenRows[$rowFingerprint])) {
                continue;
            }
            $seenRows[$rowFingerprint] = true;

            $farmaco = (string) $this->rowValue($row, ['farmaco', 'frmaco', 'farmaco_produto']);
            $produto = (string) $this->rowValue($row, ['medicamento_produto', 'medicamento', 'medicamento_produto_']);
            $quantity = $this->parseDecimalPtBr((string) $this->rowValue($row, ['quantidade_em_estoque', 'quantidade_estoque'], '0'));
            $concentration = $this->extractConcentration($produto);
            $form = $this->extractForm($produto);
            $key = implode('|', [
                $this->normalizeText($farmaco),
                $this->normalizeText($concentration),
                $this->normalizeText($form),
            ]);

            if (! isset($grouped[$key])) {
            $grouped[$key] = [
                    'farmaco_raw' => $farmaco,
                    'produto_raw' => $produto,
                    'farmaco_norm' => $this->normalizeText($farmaco),
                    'farmaco_base' => $this->normalizeIngredientBase($farmaco),
                    'produto_norm' => $this->normalizeText($produto),
                    'concentration_norm' => $this->normalizeText($concentration),
                    'form_norm' => $this->normalizeText($form),
                    'quantity' => 0,
                ];
            }

            $grouped[$key]['quantity'] += $quantity;
        }

        return array_values($grouped);
    }

    private function buildCsvRowFingerprint(array $row): string
    {
        $parts = [
            $this->normalizeText($this->rowValue($row, ['cdigo_do_estoque', 'codigo_do_estoque', 'codigo_estoque'])),
            $this->normalizeText($this->rowValue($row, ['cod_sigaf_medicamento_produto', 'cod_sigaf_medicamento_produto_'])),
            $this->normalizeText($this->rowValue($row, ['cod_siad_medicamento_produto', 'cod_siad_medicamento_produto_'])),
            $this->normalizeText($this->rowValue($row, ['medicamento_produto', 'medicamento'])),
            $this->normalizeText($this->rowValue($row, ['fabricante', 'fabricante_'])),
            $this->normalizeText($this->rowValue($row, ['cnpj_fabricante', 'cnpj_fabricante_'])),
            $this->normalizeText($this->rowValue($row, ['ean'])),
            $this->normalizeText($this->rowValue($row, ['lote'])),
            $this->normalizeText($this->rowValue($row, ['validade'])),
            $this->normalizeText($this->rowValue($row, ['bloqueado'])),
            $this->normalizeText($this->rowValue($row, ['motivo_do_bloqueio'])),
            (string) $this->parseDecimalPtBr((string) $this->rowValue($row, ['quantidade_em_estoque', 'quantidade_estoque'], '0')),
        ];

        return sha1(implode('|', $parts));
    }

    private function rowValue(array $row, array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && trim((string) $row[$key]) !== '') {
                return (string) $row[$key];
            }
        }

        return $default;
    }

    private function matchMedicine(array $item, $medicines): array
    {
        $scored = [];
        foreach ($medicines as $medicine) {
            $ingredientNorm = $this->normalizeText((string) $medicine->active_ingredient);
            $ingredientBase = $this->normalizeIngredientBase((string) $medicine->active_ingredient);
            $concentrationNorm = $this->normalizeText((string) $medicine->concentration);
            $formNorm = $this->canonicalForm((string) $medicine->pharmaceutical_form);

            $score = 0;
            if ($item['farmaco_norm'] !== '' && $ingredientNorm === $item['farmaco_norm']) {
                $score += 60;
            }
            if ($item['farmaco_base'] !== '' && $ingredientBase === $item['farmaco_base']) {
                $score += 30;
            }
            if ($item['concentration_norm'] !== '' && $concentrationNorm === $item['concentration_norm']) {
                $score += 30;
            }
            if ($item['form_norm'] !== '' && $formNorm === $item['form_norm']) {
                $score += 10;
            }

            if ($score === 0) {
                if ($item['farmaco_norm'] !== '' && str_contains($ingredientNorm, $item['farmaco_norm'])) {
                    $score += 20;
                }
                if ($item['farmaco_base'] !== '' && str_contains($ingredientBase, $item['farmaco_base'])) {
                    $score += 20;
                }
                if ($item['concentration_norm'] !== '' && str_contains($concentrationNorm, $item['concentration_norm'])) {
                    $score += 15;
                }
            }

            if ($score > 0) {
                $scored[] = [
                    'medicine_id' => $medicine->id,
                    'label' => trim($medicine->active_ingredient.' '.$medicine->concentration.' '.$medicine->pharmaceutical_form),
                    'score' => $score,
                ];
            }
        }

        if (empty($scored)) {
            return ['status' => 'unmatched', 'reason' => 'Nenhum medicamento candidato encontrado por princípio ativo/concentração/forma.'];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $top = $scored[0];
        $minScore = $item['farmaco_base'] !== '' ? 60 : 75;
        if ($top['score'] < $minScore) {
            return ['status' => 'unmatched', 'reason' => 'Correspondência abaixo do limiar mínimo de confiança para atualização automática.'];
        }

        $ties = array_values(array_filter($scored, fn ($candidate) => $candidate['score'] === $top['score']));
        if (count($ties) > 1) {
            // desempate por label mais próximo ao texto completo do produto
            $produto = $item['produto_norm'] ?? '';
            usort($ties, function ($a, $b) use ($produto) {
                similar_text($a['label'], $produto, $pa);
                similar_text($b['label'], $produto, $pb);
                return $pb <=> $pa;
            });
            if (count($ties) > 1) {
                similar_text($ties[0]['label'], $produto, $p1);
                similar_text($ties[1]['label'], $produto, $p2);
                if (($p1 - $p2) >= 5) {
                    return ['status' => 'matched', 'medicine_id' => $ties[0]['medicine_id']];
                }
            }

            return [
                'status' => 'ambiguous',
                'candidates' => array_slice(array_map(fn ($candidate) => [
                    'medicine_id' => $candidate['medicine_id'],
                    'label' => $candidate['label'],
                ], $ties), 0, 5),
            ];
        }

        return ['status' => 'matched', 'medicine_id' => $top['medicine_id']];
    }

    private function parseDecimalPtBr(string $value): float
    {
        $normalized = str_replace('.', '', $value);
        $normalized = str_replace(',', '.', $normalized);
        $normalized = preg_replace('/[^0-9\.\-]/', '', $normalized ?? '');

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    private function parseDecimalSmart(string $value): float
    {
        $raw = trim($value);
        if ($raw === '') {
            return 0.0;
        }

        $raw = preg_replace('/[^0-9,\.\-]/', '', $raw ?? '');
        if ($raw === null || $raw === '') {
            return 0.0;
        }

        $hasComma = str_contains($raw, ',');
        $hasDot = str_contains($raw, '.');

        if ($hasComma && $hasDot) {
            $lastComma = strrpos($raw, ',');
            $lastDot = strrpos($raw, '.');
            if ($lastComma !== false && $lastDot !== false) {
                if ($lastComma > $lastDot) {
                    // 1.234.567,89
                    $raw = str_replace('.', '', $raw);
                    $raw = str_replace(',', '.', $raw);
                } else {
                    // 1,234,567.89
                    $raw = str_replace(',', '', $raw);
                }
            }
        } elseif ($hasComma) {
            // 1234,56
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif ($hasDot && substr_count($raw, '.') > 1) {
            // 1.234.567
            $raw = str_replace('.', '', $raw);
        }

        return is_numeric($raw) ? (float) $raw : 0.0;
    }

    private function normalizeHeader(string $header): string
    {
        $header = $this->toUtf8($header);
        $normalized = Str::ascii(mb_strtolower(trim($header)));
        $normalized = preg_replace('/[^a-z0-9]+/', '_', $normalized ?? '');

        return trim((string) $normalized, '_');
    }

    private function normalizeText(string $value): string
    {
        $value = $this->toUtf8($value);
        $normalized = Str::ascii(mb_strtoupper(trim($value)));
        $normalized = preg_replace('/[^A-Z0-9\+\/\.\- ]+/', ' ', $normalized ?? '');
        $normalized = preg_replace('/\s+/', ' ', $normalized ?? '');

        return trim((string) $normalized);
    }

    private function toUtf8(?string $value): string
    {
        $value = (string) ($value ?? '');
        if ($value === '') {
            return '';
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $converted = @mb_convert_encoding($value, 'UTF-8', 'Windows-1252,ISO-8859-1,UTF-8');
        if ($converted === false) {
            $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
        }

        return is_string($converted) ? $converted : $value;
    }

    private function extractConcentration(string $text): string
    {
        $normalized = $this->normalizeText($text);
        if (preg_match('/\b\d+(?:[\,\.]\d+)?(?:\+\d+(?:[\,\.]\d+)?)*(?:\s*\/\s*\d+(?:[\,\.]\d+)?)?\s*(?:MG\/ML|MG\/G|MCG\/DOSE|MG\/DOSE|MCG|MG|G|ML|UI|%)\b/', $normalized, $matches)) {
            return $this->normalizeConcentrationToken($matches[0]);
        }

        return '';
    }

    private function extractForm(string $text): string
    {
        $normalized = $this->normalizeText($text);
        foreach (self::FORM_SYNONYMS as $term => $canonical) {
            if (str_contains($normalized, $term)) {
                return $canonical;
            }
        }

        return '';
    }

    private function canonicalForm(string $form): string
    {
        $normalized = $this->normalizeText($form);
        foreach (self::FORM_SYNONYMS as $term => $canonical) {
            if (str_contains($normalized, $term)) {
                return $canonical;
            }
        }

        return $normalized;
    }

    private function normalizeIngredientBase(string $value): string
    {
        $normalized = $this->normalizeText($value);
        $tokensToRemove = [
            'CLORIDRATO',
            'SODICO',
            'SODICA',
            'MALEATO',
            'ACETATO',
            'CLORIDRICO',
            'POTASSIO',
            'POTASSICA',
            'MONOIDRATADO',
            'BESILATO',
            'SUCCINATO',
            'DICLORIDRATO',
        ];
        $pattern = '/\b('.implode('|', $tokensToRemove).')\b/u';
        $normalized = preg_replace($pattern, ' ', $normalized ?? '');
        $normalized = preg_replace('/\s+/', ' ', $normalized ?? '');

        return trim((string) $normalized);
    }

    private function normalizeConcentrationToken(string $token): string
    {
        $token = str_replace(',', '.', $this->normalizeText($token));
        $token = preg_replace('/\s+/', ' ', $token ?? '');
        $token = str_replace(' / ', '/', $token);

        return trim((string) $token);
    }

    private function normalizeInternalCode(string $internalCode): string
    {
        $code = strtoupper(trim($this->toUtf8($internalCode)));
        $code = preg_replace('/\s+/', '', $code ?? '');
        if ($code === null || $code === '') {
            return '';
        }

        // preserva códigos alfanuméricos, mas remove zeros à esquerda quando o código for puramente numérico
        if (preg_match('/^\d+$/', $code)) {
            $trimmed = ltrim($code, '0');
            return $trimmed === '' ? '0' : $trimmed;
        }

        return $code;
    }
}
