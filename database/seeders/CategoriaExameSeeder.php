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

        // Seeder roda fora de requisição: grava explicitamente na Saúde.
        $sec = \App\Models\Secretaria::saudeId();

        foreach ($categorias as $nome) {
            DB::table('categoria_exames')->insertOrIgnore([
                'secretaria_id' => $sec,
                'nome' => $nome,
                'ativo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
