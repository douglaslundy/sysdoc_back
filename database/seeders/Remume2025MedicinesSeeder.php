<?php

namespace Database\Seeders;

use App\Models\MedicineItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Remume2025MedicinesSeeder extends Seeder
{
    public function run(): void
    {
        $items = $this->loadItems();

        DB::transaction(function () use ($items) {
            // Limpa dependências antes do catálogo de medicamentos.
            DB::table('medicine_daily_statuses')->delete();
            DB::table('medicine_monthly_acquisitions')->delete();
            DB::table('medicine_publications')->delete();

            MedicineItem::query()->forceDelete();

            foreach ($items as $index => $rawItem) {
                $normalized = $this->normalizeItem($rawItem, $index + 1);

                MedicineItem::create([
                    'internal_code' => sprintf('REMUME-2025-%04d', $normalized['number']),
                    'brand_name' => null,
                    'active_ingredient' => $normalized['active_ingredient'],
                    'concentration' => $normalized['concentration'],
                    'pharmaceutical_form' => $normalized['pharmaceutical_form'],
                    'presentation' => $normalized['presentation'],
                    'unit_measure' => $normalized['unit_measure'],
                    'ean_code' => null,
                    'is_free_distribution' => true,
                    'is_controlled' => $normalized['is_controlled'],
                    'active' => true,
                    'technical_notes' => $normalized['technical_notes'],
                ]);
            }
        });
    }

    private function loadItems(): array
    {
        $path = database_path('seeders/data/remume_2025_parsed.json');
        if (! file_exists($path)) {
            throw new \RuntimeException('Arquivo de dados REMUME não encontrado: '.$path);
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Arquivo remume_2025_parsed.json inválido.');
        }

        return $decoded;
    }

    private function normalizeItem(array $item, int $line): array
    {
        $number = (int) ($item['num'] ?? 0);
        $raw = $this->fixEncoding(trim((string) ($item['raw'] ?? '')));
        $presentationRaw = $this->fixEncoding(trim((string) ($item['presentation'] ?? '')));

        if ($number <= 0 || $raw === '') {
            throw new \RuntimeException("Item REMUME inválido na linha {$line}.");
        }

        $fullText = trim($raw.' '.$presentationRaw);
        $fullText = preg_replace('/\s+/', ' ', (string) $fullText) ?? $fullText;

        $forms = [
            'comprimido revestido',
            'comprimido sublingual',
            'comprimido',
            'cápsula',
            'capsula',
            'suspensão oral',
            'suspensao oral',
            'solução oral',
            'solucao oral',
            'solução injetável',
            'solucao injetavel',
            'injetável',
            'injetavel',
            'xarope',
            'creme vaginal',
            'creme',
            'pomada',
            'geleia vaginal',
            'gel',
            'loção',
            'locao',
            'pasta',
            'adesivo transdermico',
            'adesivo transdérmico',
            'goma',
            'frasco-ampola',
            'ampola',
            'sachê',
            'sache',
            'gotas',
            'pó para injetável',
            'po para injetavel',
        ];

        $pharmaceuticalForm = 'Não informado';
        $ingredient = $raw;
        $presentation = $presentationRaw !== '' ? $presentationRaw : $raw;

        $lower = mb_strtolower($fullText);
        $pos = null;
        foreach ($forms as $formCandidate) {
            $current = mb_stripos($lower, $formCandidate);
            if ($current !== false && ($pos === null || $current < $pos)) {
                $pos = $current;
                $pharmaceuticalForm = $this->titleCase($formCandidate);
            }
        }

        if ($pos !== null) {
            $ingredient = trim(mb_substr($fullText, 0, $pos));
            $presentation = trim(mb_substr($fullText, $pos));
        }

        $ingredient = trim($ingredient, "-–:;,. \t\n\r\0\x0B");
        if ($ingredient === '') {
            $ingredient = $raw;
        }

        $concentration = $this->extractConcentration($fullText);
        $unitMeasure = $this->inferUnitMeasure($fullText);
        $isControlled = $this->isControlledMedicine($ingredient);
        $notes = $this->buildTechnicalNotes($fullText, $number);

        return [
            'number' => $number,
            'active_ingredient' => $ingredient,
            'concentration' => $concentration,
            'pharmaceutical_form' => $pharmaceuticalForm,
            'presentation' => $presentation !== '' ? $presentation : $raw,
            'unit_measure' => $unitMeasure,
            'is_controlled' => $isControlled,
            'technical_notes' => $notes,
        ];
    }

    private function extractConcentration(string $text): string
    {
        if (preg_match('/(\d+[.,]?\d*\s?(?:mg|mcg|g|ml|ui|%)(?:\s*\/\s*(?:ml|g))?(\s*\+\s*\d+[.,]?\d*\s?(?:mg|mcg|g|ml|ui|%)(?:\s*\/\s*(?:ml|g))?)*)/iu', $text, $m)) {
            return strtoupper(trim($m[1]));
        }

        return 'Não informado';
    }

    private function inferUnitMeasure(string $text): string
    {
        $lower = mb_strtolower($text);
        if (str_contains($lower, 'comprim')) {
            return 'cp';
        }
        if (str_contains($lower, 'cáps') || str_contains($lower, 'caps')) {
            return 'cap';
        }
        if (str_contains($lower, 'ml')) {
            return 'mL';
        }
        if (str_contains($lower, 'ui')) {
            return 'UI';
        }
        if (str_contains($lower, 'ampola')) {
            return 'amp';
        }
        if (str_contains($lower, 'frasco-ampola')) {
            return 'FA';
        }
        if (str_contains($lower, 'frasco')) {
            return 'fr';
        }
        if (str_contains($lower, 'sach')) {
            return 'un';
        }
        if (str_contains($lower, 'goma')) {
            return 'un';
        }
        if (str_contains($lower, 'adesivo')) {
            return 'un';
        }
        if (str_contains($lower, 'creme') || str_contains($lower, 'pomada') || str_contains($lower, 'gel')) {
            return 'g';
        }

        return 'un';
    }

    private function isControlledMedicine(string $ingredient): bool
    {
        $controlledNames = [
            'amitriptilina',
            'biperideno',
            'bromazepam',
            'carbamazepina',
            'carbonato de lítio',
            'clomipramina',
            'clonazepam',
            'clorpromazina',
            'diazepam',
            'fenitoína',
            'fenobarbital',
            'fluoxetina',
            'haloperidol',
            'imipramina',
            'levomepromazina',
            'metilfenidato',
            'naltrexona',
            'nortriptilina',
            'valproato',
            'talidomida',
        ];

        $normalized = mb_strtolower($ingredient);
        foreach ($controlledNames as $name) {
            if (str_contains($normalized, $name)) {
                return true;
            }
        }

        return false;
    }

    private function buildTechnicalNotes(string $fullText, int $number): string
    {
        $notes = ['Fonte: REMUME Ilicínea 2025'];

        $lower = mb_strtolower($fullText);
        if (str_contains($lower, 'farm. popular')) {
            $notes[] = 'Farmácia Popular';
        }
        if (str_contains($lower, 'uso ubs')) {
            $notes[] = 'Uso UBS';
        }
        if ($number >= 170 && $number <= 188) {
            $notes[] = 'Medicamento estratégico (dispensação condicionada conforme protocolo local)';
        }

        return implode(' | ', $notes);
    }

    private function fixEncoding(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        if (str_contains($value, 'Ã') || str_contains($value, 'Â') || str_contains($value, 'â')) {
            $fixed = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);
            if (is_string($fixed) && $fixed !== '') {
                return $fixed;
            }
        }

        return $value;
    }

    private function titleCase(string $value): string
    {
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
