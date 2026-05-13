<?php

namespace Database\Seeders;

use App\Models\MedicineItem;
use Illuminate\Database\Seeder;

class SusMgMedicinesSeeder extends Seeder
{
    /**
     * Fontes oficiais usadas para compor o seed:
     * - REMEMG (SES-MG): https://www.saude.mg.gov.br/wp-content/uploads/2026/03/REMEMG-SITE-MARCO_2026.pdf
     * - CEAF por ordem alfabética (SES-MG): https://www.saude.mg.gov.br/wp-content/uploads/2026/04/LISTA-DE-MEDICAMENTOS-DO-CEAF-POR-ORDEM-ALFABETICA-24-04-2026.pdf
     * - CEAF por doença (SES-MG): https://www.saude.mg.gov.br/wp-content/uploads/2025/01/LISTA-DE-MEDICAMENTOS-DO-CEAF-POR-DOENCA-17-01-2025.pdf
     * - Controle especial (ANVISA): https://www.gov.br/anvisa/pt-br/assuntos/medicamentos/controlados/lista-substancias
     */
    public function run(): void
    {
        $items = [
            // Componente básico / REMEMG
            ['code' => 'MG-CBAF-0001', 'ingredient' => 'Ácido acetilsalicílico', 'concentration' => '100 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0002', 'ingredient' => 'Atenolol', 'concentration' => '50 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0003', 'ingredient' => 'Captopril', 'concentration' => '25 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0004', 'ingredient' => 'Enalapril', 'concentration' => '10 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0005', 'ingredient' => 'Losartana potássica', 'concentration' => '50 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0006', 'ingredient' => 'Hidroclorotiazida', 'concentration' => '25 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0007', 'ingredient' => 'Metformina', 'concentration' => '850 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0008', 'ingredient' => 'Glibenclamida', 'concentration' => '5 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0009', 'ingredient' => 'Gliclazida', 'concentration' => '30 mg', 'form' => 'comprimido de liberação prolongada', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0010', 'ingredient' => 'Insulina humana NPH', 'concentration' => '100 UI/mL', 'form' => 'solução injetável', 'presentation' => 'frasco', 'unit' => 'mL', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0011', 'ingredient' => 'Insulina humana regular', 'concentration' => '100 UI/mL', 'form' => 'solução injetável', 'presentation' => 'frasco', 'unit' => 'mL', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0012', 'ingredient' => 'Sinvastatina', 'concentration' => '20 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0013', 'ingredient' => 'Omeprazol', 'concentration' => '20 mg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0014', 'ingredient' => 'Dipirona sódica', 'concentration' => '500 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0015', 'ingredient' => 'Paracetamol', 'concentration' => '500 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0016', 'ingredient' => 'Ibuprofeno', 'concentration' => '300 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0017', 'ingredient' => 'Amoxicilina', 'concentration' => '500 mg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0018', 'ingredient' => 'Azitromicina', 'concentration' => '500 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0019', 'ingredient' => 'Cefalexina', 'concentration' => '500 mg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0020', 'ingredient' => 'Sulfametoxazol + trimetoprima', 'concentration' => '400 mg + 80 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0021', 'ingredient' => 'Salbutamol', 'concentration' => '100 mcg/dose', 'form' => 'aerossol', 'presentation' => 'frasco', 'unit' => 'dose', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0022', 'ingredient' => 'Beclometasona dipropionato', 'concentration' => '250 mcg/dose', 'form' => 'aerossol oral', 'presentation' => 'frasco', 'unit' => 'dose', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0023', 'ingredient' => 'Prednisona', 'concentration' => '20 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0024', 'ingredient' => 'Levotiroxina sódica', 'concentration' => '50 mcg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],
            ['code' => 'MG-CBAF-0025', 'ingredient' => 'Sertralina', 'concentration' => '50 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CBAF', 'controlled' => false],

            // Componente estratégico / REMEMG/RENAME
            ['code' => 'MG-CESAF-0001', 'ingredient' => 'Rifampicina + isoniazida', 'concentration' => '300 mg + 150 mg', 'form' => 'comprimido', 'presentation' => 'blister', 'unit' => 'cp', 'component' => 'CESAF', 'controlled' => false],
            ['code' => 'MG-CESAF-0002', 'ingredient' => 'Rifampicina + isoniazida + pirazinamida + etambutol', 'concentration' => '150 mg + 75 mg + 400 mg + 275 mg', 'form' => 'comprimido', 'presentation' => 'blister', 'unit' => 'cp', 'component' => 'CESAF', 'controlled' => false],
            ['code' => 'MG-CESAF-0003', 'ingredient' => 'Oseltamivir', 'concentration' => '75 mg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CESAF', 'controlled' => false],
            ['code' => 'MG-CESAF-0004', 'ingredient' => 'Benzilpenicilina benzatina', 'concentration' => '1.200.000 UI', 'form' => 'pó para suspensão injetável', 'presentation' => 'frasco-ampola', 'unit' => 'FA', 'component' => 'CESAF', 'controlled' => false],
            ['code' => 'MG-CESAF-0005', 'ingredient' => 'Talidomida', 'concentration' => '100 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CESAF', 'controlled' => true],

            // Componente especializado / CEAF (alto custo)
            ['code' => 'MG-CEAF-0001', 'ingredient' => 'Acetazolamida', 'concentration' => '250 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0002', 'ingredient' => 'Ácido ursodesoxicólico', 'concentration' => '300 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0003', 'ingredient' => 'Adalimumabe', 'concentration' => '40 mg/0,8 mL', 'form' => 'solução injetável', 'presentation' => 'seringa preenchida', 'unit' => 'un', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0004', 'ingredient' => 'Alfacalcidol', 'concentration' => '0,25 mcg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0005', 'ingredient' => 'Alfaepoetina', 'concentration' => '4.000 UI', 'form' => 'solução injetável', 'presentation' => 'seringa preenchida', 'unit' => 'un', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0006', 'ingredient' => 'Ambrisentana', 'concentration' => '10 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0007', 'ingredient' => 'Apixabana', 'concentration' => '5 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0008', 'ingredient' => 'Azatioprina', 'concentration' => '50 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0009', 'ingredient' => 'Bosentana', 'concentration' => '125 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0010', 'ingredient' => 'Cabergolina', 'concentration' => '0,5 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0011', 'ingredient' => 'Calcitriol', 'concentration' => '0,25 mcg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0012', 'ingredient' => 'Carbonato de cálcio', 'concentration' => '1.250 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0013', 'ingredient' => 'Certolizumabe pegol', 'concentration' => '200 mg/mL', 'form' => 'solução injetável', 'presentation' => 'seringa preenchida', 'unit' => 'un', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0014', 'ingredient' => 'Ciclosporina', 'concentration' => '100 mg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0015', 'ingredient' => 'Cinacalcete', 'concentration' => '30 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0016', 'ingredient' => 'Clobazam', 'concentration' => '10 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0017', 'ingredient' => 'Clonazepam', 'concentration' => '2 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0018', 'ingredient' => 'Clozapina', 'concentration' => '100 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0019', 'ingredient' => 'Dalfampridina', 'concentration' => '10 mg', 'form' => 'comprimido de liberação prolongada', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0020', 'ingredient' => 'Diazepam', 'concentration' => '10 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0021', 'ingredient' => 'Etanercepte', 'concentration' => '50 mg/mL', 'form' => 'solução injetável', 'presentation' => 'seringa preenchida', 'unit' => 'un', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0022', 'ingredient' => 'Fingolimode', 'concentration' => '0,5 mg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0023', 'ingredient' => 'Golimumabe', 'concentration' => '50 mg/0,5 mL', 'form' => 'solução injetável', 'presentation' => 'seringa preenchida', 'unit' => 'un', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0024', 'ingredient' => 'Infliximabe', 'concentration' => '100 mg', 'form' => 'pó para solução injetável', 'presentation' => 'frasco-ampola', 'unit' => 'FA', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0025', 'ingredient' => 'Levetiracetam', 'concentration' => '500 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0026', 'ingredient' => 'Metilfenidato', 'concentration' => '10 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0027', 'ingredient' => 'Micofenolato de mofetila', 'concentration' => '500 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0028', 'ingredient' => 'Natalizumabe', 'concentration' => '300 mg/15 mL', 'form' => 'solução para infusão', 'presentation' => 'frasco', 'unit' => 'mL', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0029', 'ingredient' => 'Olanzapina', 'concentration' => '10 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0030', 'ingredient' => 'Quetiapina', 'concentration' => '200 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0031', 'ingredient' => 'Risperidona', 'concentration' => '2 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0032', 'ingredient' => 'Sildenafila', 'concentration' => '20 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0033', 'ingredient' => 'Tacrolimo', 'concentration' => '1 mg', 'form' => 'cápsula', 'presentation' => 'caixa', 'unit' => 'cap', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0034', 'ingredient' => 'Tofacitinibe', 'concentration' => '5 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0035', 'ingredient' => 'Ustequinumabe', 'concentration' => '45 mg/0,5 mL', 'form' => 'solução injetável', 'presentation' => 'seringa preenchida', 'unit' => 'un', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0036', 'ingredient' => 'Valganciclovir', 'concentration' => '450 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => false],
            ['code' => 'MG-CEAF-0037', 'ingredient' => 'Valproato de sódio', 'concentration' => '500 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0038', 'ingredient' => 'Vigabatrina', 'concentration' => '500 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0039', 'ingredient' => 'Topiramato', 'concentration' => '50 mg', 'form' => 'comprimido revestido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
            ['code' => 'MG-CEAF-0040', 'ingredient' => 'Lamotrigina', 'concentration' => '100 mg', 'form' => 'comprimido', 'presentation' => 'caixa', 'unit' => 'cp', 'component' => 'CEAF', 'controlled' => true],
        ];

        $items = array_merge($items, $this->loadExternalItems());
        $items = $this->normalizeAndValidateItems($items);

        foreach ($items as $item) {
            $component = $item['component'];

            MedicineItem::updateOrCreate(
                ['internal_code' => $item['code']],
                [
                    'brand_name' => null,
                    'active_ingredient' => $item['ingredient'],
                    'concentration' => $item['concentration'],
                    'pharmaceutical_form' => $item['form'],
                    'presentation' => $item['presentation'],
                    'unit_measure' => $item['unit'],
                    'ean_code' => null,
                    'is_free_distribution' => true,
                    'is_controlled' => $item['controlled'],
                    'active' => true,
                    'technical_notes' => "Fonte oficial SES-MG ({$component}) + RENAME/ANVISA controle especial. Seed inicial de referência.",
                ]
            );
        }
    }

    private function loadExternalItems(): array
    {
        $path = database_path('seeders/data/sus_mg_medicines.extend.json');
        if (! file_exists($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (! is_array($decoded)) {
            throw new \RuntimeException('Arquivo sus_mg_medicines.extend.json inválido: JSON malformado.');
        }

        return $decoded;
    }

    private function normalizeAndValidateItems(array $items): array
    {
        $required = ['code', 'ingredient', 'concentration', 'form', 'presentation', 'unit', 'component', 'controlled'];
        $validComponents = ['CBAF', 'CESAF', 'CEAF'];
        $codes = [];
        $normalized = [];

        foreach ($items as $index => $item) {
            if (! is_array($item)) {
                throw new \RuntimeException("Item #{$index} inválido: esperado objeto.");
            }

            foreach ($required as $field) {
                if (! array_key_exists($field, $item)) {
                    throw new \RuntimeException("Item #{$index} inválido: campo obrigatório ausente ({$field}).");
                }
            }

            $code = trim((string) $item['code']);
            if ($code === '') {
                throw new \RuntimeException("Item #{$index} inválido: code vazio.");
            }
            if (isset($codes[$code])) {
                throw new \RuntimeException("Código duplicado no seed: {$code}.");
            }
            $codes[$code] = true;

            $component = strtoupper(trim((string) $item['component']));
            if (! in_array($component, $validComponents, true)) {
                throw new \RuntimeException("Item {$code} inválido: component deve ser CBAF, CESAF ou CEAF.");
            }

            $normalized[] = [
                'code' => $code,
                'ingredient' => trim((string) $item['ingredient']),
                'concentration' => trim((string) $item['concentration']),
                'form' => trim((string) $item['form']),
                'presentation' => trim((string) $item['presentation']),
                'unit' => trim((string) $item['unit']),
                'component' => $component,
                'controlled' => (bool) $item['controlled'],
            ];
        }

        return $normalized;
    }
}
