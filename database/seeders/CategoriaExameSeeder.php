<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriaExameSeeder extends Seeder
{
    public function run(): void
    {
        $categorias = [
            'ANATOMIA PATOLÓGICA',
            'BANCO DE SANGUE / IMUNOHEMATOLOGIA',
            'BIOLOGIA MOLECULAR',
            'BIOQUÍMICA',
            'CITOGENÉTICA',
            'CITOLOGIA',
            'COAGULAÇÃO / HEMOSTASIA',
            'DIABETES',
            'DOENÇAS AUTOIMUNES',
            'DOENÇAS INFECCIOSAS',
            'ENZIMOLOGIA',
            'FUNÇÃO HEPÁTICA',
            'FUNÇÃO PANCREÁTICA',
            'FUNÇÃO RENAL',
            'FUNÇÃO TIREOIDIANA',
            'GASOMETRIA',
            'GENÉTICA',
            'HEMATOLOGIA',
            'HISTOLOGIA',
            'HORMÔNIOS',
            'IMUNOLOGIA',
            'IST / DST',
            'LÍQUIDOS CORPORAIS',
            'LIPIDOGRAMA',
            'MARCADORES CARDÍACOS',
            'MARCADORES TUMORAIS',
            'MICROBIOLOGIA / BACTERIOLOGIA',
            'MINERAIS E ELETRÓLITOS',
            'PARASITOLOGIA',
            'PROTEÍNAS',
            'SOROLOGIAS',
            'TOXICOLOGIA',
            'URIANÁLISE',
            'VIROLOGIA',
            'VITAMINAS E MICRONUTRIENTES',
        ];

        foreach ($categorias as $nome) {
            DB::table('categoria_exames')->insertOrIgnore([
                'nome'       => $nome,
                'ativo'      => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
