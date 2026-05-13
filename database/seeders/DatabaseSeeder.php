<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,                    // Usuario admin padrao
            StateSeeder::class,                   // Estados brasileiros
            AccessProfileSeeder::class,           // Perfis de acesso + paginas + permissoes
            CategoriaExameSeeder::class,          // Categorias de exames laboratoriais
            ExamesCompletosSeeder::class,         // Catalogo completo de exames
            EstabelecimentosAlvarasSeeder::class, // Estabelecimentos e alvaras de Ilicinea
            PharmacyCatalogSeeder::class,         // Catalogos auxiliares da Farmacia
            SusMgMedicinesSeeder::class,          // Medicamentos SUS-MG (REMEMG/CEAF + controlados)
        ]);
    }
}
