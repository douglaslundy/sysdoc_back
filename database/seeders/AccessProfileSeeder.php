<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccessProfileSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('system_pages')
            ->where('path', '/dashboards')
            ->update(['titulo' => 'Dashboard', 'path' => '/dashboard', 'updated_at' => now()]);

        $pages = [
            ['titulo' => 'Dashboard', 'path' => '/dashboard', 'icone' => 'pie-chart', 'categoria' => 'Geral'],
            ['titulo' => 'Usuários', 'path' => '/users', 'icone' => 'user', 'categoria' => 'Administração'],
            ['titulo' => 'Clientes', 'path' => '/clients', 'icone' => 'users', 'categoria' => 'Cadastros'],
            ['titulo' => 'Relatório Clientes', 'path' => '/client_report', 'icone' => 'bar-chart-2', 'categoria' => 'Relatórios'],
            ['titulo' => 'Lab - Exames', 'path' => '/laboratorio/exames', 'icone' => 'thermometer', 'categoria' => 'Laboratório'],
            ['titulo' => 'Lab - Pedidos', 'path' => '/laboratorio/pedidos', 'icone' => 'clipboard', 'categoria' => 'Laboratório'],
            ['titulo' => 'Lab - Categorias', 'path' => '/laboratorio/categorias', 'icone' => 'tag', 'categoria' => 'Laboratório'],
            ['titulo' => 'Lab - Médicos', 'path' => '/laboratorio/medicos', 'icone' => 'user-check', 'categoria' => 'Laboratório'],
            ['titulo' => 'Lab - Agenda', 'path' => '/laboratorio/agenda', 'icone' => 'calendar', 'categoria' => 'Laboratório'],
            ['titulo' => 'Especialidades', 'path' => '/specialities', 'icone' => 'award', 'categoria' => 'Cadastros'],
            ['titulo' => 'Fila', 'path' => '/queue', 'icone' => 'layers', 'categoria' => 'Atendimento'],
            ['titulo' => 'Veículos', 'path' => '/vehicles', 'icone' => 'truck', 'categoria' => 'TFD'],
            ['titulo' => 'Rotas', 'path' => '/routes', 'icone' => 'map', 'categoria' => 'TFD'],
            ['titulo' => 'Viagens', 'path' => '/trips', 'icone' => 'map-pin', 'categoria' => 'TFD'],
            ['titulo' => 'Ofícios', 'path' => '/letters', 'icone' => 'send', 'categoria' => 'Documentos'],
            ['titulo' => 'Portarias', 'path' => '/ordinance', 'icone' => 'file-text', 'categoria' => 'Documentos'],
            ['titulo' => 'Modelos IA', 'path' => '/models', 'icone' => 'cpu', 'categoria' => 'Administração'],
            ['titulo' => 'Logs de Erro', 'path' => '/errorlogs', 'icone' => 'alert-triangle', 'categoria' => 'Administração'],
            ['titulo' => 'Logs de QRCODE', 'path' => '/qrcodelogs', 'icone' => 'maximize', 'categoria' => 'Administração'],
            ['titulo' => 'Perfis de Acesso', 'path' => '/perfis', 'icone' => 'shield', 'categoria' => 'Administração'],
            ['titulo' => 'Páginas do Sistema', 'path' => '/paginas-sistema', 'icone' => 'layout', 'categoria' => 'Administração'],
            ['titulo' => 'Categorias de Páginas', 'path' => '/paginas-categorias', 'icone' => 'tag', 'categoria' => 'Administração'],
            ['titulo' => 'Auditoria', 'path' => '/auditoria', 'icone' => 'eye', 'categoria' => 'Administração'],
            ['titulo' => 'Avisos', 'path' => '/avisos', 'icone' => 'bell', 'categoria' => 'Administração'],
            ['titulo' => 'Status dos Painéis', 'path' => '/painel-esus/statuses', 'icone' => 'monitor', 'categoria' => 'Administração'],
            ['titulo' => 'Avisos', 'path' => '/avisos', 'icone' => 'bell', 'categoria' => 'Administração'],
            ['titulo' => 'Lab - Resultados', 'path' => '/laboratorio/resultados', 'icone' => 'file-text', 'categoria' => 'Laboratório'],
            ['titulo' => 'Estabelecimentos', 'path' => '/estabelecimentos', 'icone' => 'home', 'categoria' => 'Vigilância Sanitária'],
            ['titulo' => 'Alvarás', 'path' => '/alvaras', 'icone' => 'award', 'categoria' => 'Vigilância Sanitária'],
            ['titulo' => 'Vigilância - Config', 'path' => '/vigilancia/configuracoes', 'icone' => 'settings', 'categoria' => 'Vigilância Sanitária'],
            ['titulo' => 'Farmácia - Medicamentos', 'path' => '/pharmacy/medicines', 'icone' => 'archive', 'categoria' => 'Farmácia'],
            ['titulo' => 'Farmácia - Status Diário', 'path' => '/pharmacy/daily-status', 'icone' => 'calendar', 'categoria' => 'Farmácia'],
            ['titulo' => 'Farmácia - Aquisições Mensais', 'path' => '/pharmacy/monthly-acquisitions', 'icone' => 'bar-chart-2', 'categoria' => 'Farmácia'],
            ['titulo' => 'Farmácia - Conformidade', 'path' => '/pharmacy/compliance', 'icone' => 'check-square', 'categoria' => 'Farmácia'],
        ];

        foreach ($pages as $page) {
            DB::table('system_pages')->updateOrInsert(
                ['path' => $page['path']],
                [
                    'titulo' => $page['titulo'],
                    'icone' => $page['icone'],
                    'categoria' => $page['categoria'],
                    'ativo' => true,
                    'updated_at' => now(),
                ]
            );
        }

        $profiles = [
            ['nome' => 'Administrador', 'slug' => 'admin', 'descricao' => 'Acesso total ao sistema'],
            ['nome' => 'Gerente', 'slug' => 'manager', 'descricao' => 'Acesso ao laboratório e documentos'],
            ['nome' => 'Usuário', 'slug' => 'user', 'descricao' => 'Acesso básico a clientes e pedidos'],
            ['nome' => 'TFD', 'slug' => 'tfd', 'descricao' => 'Acesso a TFD, viagens e documentos'],
            ['nome' => 'Motorista', 'slug' => 'driver', 'descricao' => 'Acesso ao painel e viagens'],
            ['nome' => 'Parceiro', 'slug' => 'partner', 'descricao' => 'Acesso apenas a clientes'],
        ];

        foreach ($profiles as $profile) {
            DB::table('access_profiles')->updateOrInsert(
                ['slug' => $profile['slug']],
                [
                    'nome' => $profile['nome'],
                    'descricao' => $profile['descricao'],
                    'ativo' => true,
                    'updated_at' => now(),
                ]
            );
        }

        $permissoes = [
            'admin' => [
                '/dashboard', '/users', '/clients', '/client_report', '/laboratorio/exames', '/laboratorio/pedidos', '/laboratorio/resultados',
                '/laboratorio/categorias', '/laboratorio/medicos', '/laboratorio/agenda', '/specialities', '/queue', '/vehicles', '/routes', '/trips',
                '/letters', '/ordinance', '/models', '/errorlogs',
                '/qrcodelogs', '/perfis', '/paginas-sistema', '/paginas-categorias', '/auditoria', '/estabelecimentos', '/alvaras', '/vigilancia/configuracoes',
                '/pharmacy/medicines', '/pharmacy/daily-status', '/pharmacy/monthly-acquisitions', '/pharmacy/compliance',
                '/painel-esus/statuses', '/avisos',
            ],
            'manager' => [
                '/dashboard', '/clients', '/client_report', '/laboratorio/exames', '/laboratorio/pedidos', '/laboratorio/resultados',
                '/laboratorio/categorias', '/laboratorio/medicos', '/laboratorio/agenda', '/queue', '/trips', '/letters', '/ordinance',
                '/estabelecimentos', '/alvaras', '/pharmacy/medicines', '/pharmacy/daily-status', '/pharmacy/monthly-acquisitions', '/pharmacy/compliance',
            ],
            'user' => ['/clients', '/laboratorio/pedidos', '/laboratorio/resultados', '/laboratorio/agenda', '/queue'],
            'tfd' => ['/clients', '/client_report', '/vehicles', '/routes', '/trips', '/letters', '/ordinance', '/queue'],
            'driver' => ['/trips'],
            'partner' => ['/clients'],
        ];

        foreach ($permissoes as $slug => $paths) {
            $profile = DB::table('access_profiles')->where('slug', $slug)->first();
            if (! $profile) {
                continue;
            }

            foreach ($paths as $path) {
                $page = DB::table('system_pages')->where('path', $path)->first();
                if (! $page) {
                    continue;
                }

                DB::table('profile_page_permissions')->updateOrInsert(
                    [
                        'access_profile_id' => $profile->id,
                        'system_page_id' => $page->id,
                    ],
                    [
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
