<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,          // Usuário admin padrão
            StateSeeder::class,         // Estados brasileiros
            AccessProfileSeeder::class, // Perfis de acesso + páginas + permissões
            CategoriaExameSeeder::class,          // Categorias de exames laboratoriais
            ExamesCompletosSeeder::class,         // Catálogo completo de exames
            EstabelecimentosAlvarasSeeder::class, // Estabelecimentos e alvarás de Ilicínea (importação inicial)
        ]);
    }
}
