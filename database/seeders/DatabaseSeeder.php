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
            LegacyMenuCleanupSeeder::class,       // Remove categorias e pagina legadas do menu
            CategoriaExameSeeder::class,          // Categorias de exames laboratoriais
            ExamesCompletosSeeder::class,         // Catalogo completo de exames
            CnaesOficiaisSeeder::class,           // Tabela oficial de CNAEs (IBGE/CONCLA)
            EstabelecimentosAlvarasSeeder::class, // Estabelecimentos e alvaras de Ilicinea
            EstabVisaComplementoProducaoSeeder::class, // Complemento de estabelecimentos/alvaras EstabVisa
            ReconciliarCnaesEstabvisaSeeder::class, // Reconcilia CNAEs a partir do dump antigo + planilhas EstabVisa
            PharmacyCatalogSeeder::class,         // Catalogos auxiliares da Farmacia
            SusMgMedicinesSeeder::class,          // Medicamentos SUS-MG (REMEMG/CEAF + controlados)
            MonitorApsPageSeeder::class,          // Monitor APS - categorias, paginas e permissoes
            DashboardTabPageSeeder::class,        // Dashboard - abas como system_pages com permissoes por perfil
            ConformidadeCidadaoPageSeeder::class, // Conformidade de Cidadãos - pagina e permissoes
            RtAccessFixSeeder::class,             // Backfill de permissoes da RT
            AlmoxarifadoPageSeeder::class,        // Almoxarifado - categorias, paginas e permissoes
        ]);
    }
}
