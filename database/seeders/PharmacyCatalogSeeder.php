<?php

namespace Database\Seeders;

use App\Models\PharmacyAcquisitionSource;
use App\Models\PharmacyPharmaceuticalForm;
use App\Models\PharmacyPresentation;
use App\Models\PharmacyUnit;
use Illuminate\Database\Seeder;

class PharmacyCatalogSeeder extends Seeder
{
    /**
     * Referências para composição dos catálogos:
     * - REMEMG/SES-MG: https://www.saude.mg.gov.br/obtermedicamentos/relacao-de-medicamentos-essenciais-do-estado-de-minas-gerais-rememg/
     * - CEAF/SES-MG: https://www.saude.mg.gov.br/obtermedicamentos/ceaf/
     * - RENAME (MS): https://www.gov.br/saude/pt-br/composicao/sectics/rename
     * - Bulário ANVISA (formas farmacêuticas e apresentações): https://consultas.anvisa.gov.br/#/bulario/
     */
    public function run(): void
    {
        $units = [
            ['code' => 'cp', 'name' => 'Comprimido'],
            ['code' => 'cap', 'name' => 'Cápsula'],
            ['code' => 'mL', 'name' => 'Mililitro'],
            ['code' => 'L', 'name' => 'Litro'],
            ['code' => 'g', 'name' => 'Grama'],
            ['code' => 'mg', 'name' => 'Miligrama'],
            ['code' => 'mcg', 'name' => 'Micrograma'],
            ['code' => 'kg', 'name' => 'Quilograma'],
            ['code' => 'UI', 'name' => 'Unidade Internacional'],
            ['code' => 'un', 'name' => 'Unidade'],
            ['code' => 'dose', 'name' => 'Dose'],
            ['code' => 'FA', 'name' => 'Frasco-ampola'],
            ['code' => 'amp', 'name' => 'Ampola'],
            ['code' => 'fr', 'name' => 'Frasco'],
            ['code' => 'bl', 'name' => 'Blister'],
            ['code' => 'bg', 'name' => 'Bisnaga'],
        ];

        foreach ($units as $unit) {
            PharmacyUnit::updateOrCreate(
                ['code' => $unit['code']],
                ['name' => $unit['name'], 'active' => true]
            );
        }

        $sources = [
            'Licitação',
            'Pregão',
            'Ata de Registro de Preços',
            'Compra Direta',
            'Transferência Estadual',
            'Transferência Federal',
            'Doação',
            'Contrato',
            'Judicialização',
            'Outro',
        ];

        foreach ($sources as $source) {
            PharmacyAcquisitionSource::updateOrCreate(
                ['name' => $source],
                ['active' => true]
            );
        }

        $forms = [
            'Comprimido',
            'Comprimido revestido',
            'Comprimido de liberação prolongada',
            'Comprimido de liberação entérica',
            'Cápsula',
            'Cápsula de liberação entérica',
            'Cápsula mole',
            'Solução injetável',
            'Pó para solução injetável',
            'Pó para suspensão injetável',
            'Solução para infusão',
            'Suspensão oral',
            'Suspensão oftálmica',
            'Solução oral',
            'Pomada',
            'Creme',
            'Gel',
            'Loção',
            'Spray nasal',
            'Aerossol',
            'Aerossol oral',
            'Xarope',
            'Gotas',
            'Caneta injetora',
            'Seringa preenchida',
        ];

        foreach ($forms as $form) {
            PharmacyPharmaceuticalForm::updateOrCreate(
                ['name' => $form],
                ['active' => true]
            );
        }

        $presentations = [
            'Caixa',
            'Blister',
            'Frasco',
            'Frasco-ampola',
            'Ampola',
            'Seringa preenchida',
            'Caneta injetora',
            'Bisnaga',
            'Sachê',
            'Envelope',
            'Cartucho',
            'Kit',
            'Unidade',
        ];

        foreach ($presentations as $presentation) {
            PharmacyPresentation::updateOrCreate(
                ['name' => $presentation],
                ['active' => true]
            );
        }
    }
}
