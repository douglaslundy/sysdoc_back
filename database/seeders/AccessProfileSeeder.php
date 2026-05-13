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
            ['titulo' => 'Serviços', 'path' => '/service_calls', 'icone' => 'tool', 'categoria' => 'Administração'],
            ['titulo' => 'Salas', 'path' => '/rooms', 'icone' => 'grid', 'categoria' => 'Atendimento'],
            ['titulo' => 'Minha Sala', 'path' => '/listing_calls', 'icone' => 'monitor', 'categoria' => 'Atendimento'],
            ['titulo' => 'Em Atendimento', 'path' => '/attending', 'icone' => 'activity', 'categoria' => 'Atendimento'],
            ['titulo' => 'Novo Atendimento', 'path' => '/call', 'icone' => 'plus-circle', 'categoria' => 'Atendimento'],
            ['titulo' => 'Painel', 'path' => '/panel', 'icone' => 'layout', 'categoria' => 'Administração'],
            ['titulo' => 'Logs de Erro', 'path' => '/errorlogs', 'icone' => 'alert-triangle', 'categoria' => 'Administração'],
            ['titulo' => 'Logs de QRCODE', 'path' => '/qrcodelogs', 'icone' => 'maximize', 'categoria' => 'Administração'],
            ['titulo' => 'Perfis de Acesso', 'path' => '/perfis', 'icone' => 'shield', 'categoria' => 'Administração'],
            ['titulo' => 'Páginas do Sistema', 'path' => '/paginas-sistema', 'icone' => 'layout', 'categoria' => 'Administração'],
            ['titulo' => 'Auditoria', 'path' => '/auditoria', 'icone' => 'eye', 'categoria' => 'Administração'],
            ['titulo' => 'Lab - Resultados', 'path' => '/laboratorio/resultados', 'icone' => 'file-text', 'categoria' => 'Laboratório'],
            ['titulo' => 'Estabelecimentos', 'path' => '/estabelecimentos', 'icone' => 'home', 'categoria' => 'Vigilância Sanitária'],
            ['titulo' => 'Alvarás', 'path' => '/alvaras', 'icone' => 'award', 'categoria' => 'Vigilância Sanitária'],
            ['titulo' => 'Vigilância - Config', 'path' => '/vigilancia/configuracoes', 'icone' => 'settings', 'categoria' => 'Vigilância Sanitária'],
            ['titulo' => 'Farmácia - Medicamentos', 'path' => '/pharmacy/medicines', 'icone' => 'archive', 'categoria' => 'Farmácia'],
            ['titulo' => 'Farmácia - Status Diário', 'path' => '/pharmacy/daily-status', 'icone' => 'calendar', 'categoria' => 'Farmácia'],
            ['titulo' => 'Farmácia - Aquisições Mensais', 'path' => '/pharmacy/monthly-acquisitions', 'icone' => 'bar-chart-2', 'categoria' => 'Farmácia'],
            ['titulo' => 'Farmácia - Conformidade', 'path' => '/pharmacy/compliance', 'icone' => 'check-square', 'categoria' => 'Farmácia'],
        ];

        foreach ($pages as &$page) {
            $page['ativo'] = true;
            $page['created_at'] = now();
            $page['updated_at'] = now();
        }

        DB::table('system_pages')->insertOrIgnore($pages);

        $profiles = [
            ['nome' => 'Administrador', 'slug' => 'admin', 'descricao' => 'Acesso total ao sistema'],
            ['nome' => 'Gerente', 'slug' => 'manager', 'descricao' => 'Acesso ao laboratório e documentos'],
            ['nome' => 'Usuário', 'slug' => 'user', 'descricao' => 'Acesso básico a clientes e pedidos'],
            ['nome' => 'TFD', 'slug' => 'tfd', 'descricao' => 'Acesso a TFD, viagens e documentos'],
            ['nome' => 'Motorista', 'slug' => 'driver', 'descricao' => 'Acesso ao painel e viagens'],
            ['nome' => 'Parceiro', 'slug' => 'partner', 'descricao' => 'Acesso apenas a clientes'],
        ];

        foreach ($profiles as &$profile) {
            $profile['ativo'] = true;
            $profile['created_at'] = now();
            $profile['updated_at'] = now();
        }

        DB::table('access_profiles')->insertOrIgnore($profiles);

        $permissoes = [
            'admin' => [
                '/dashboard', '/users', '/clients', '/client_report', '/laboratorio/exames', '/laboratorio/pedidos', '/laboratorio/resultados',
                '/laboratorio/categorias', '/laboratorio/medicos', '/laboratorio/agenda', '/specialities', '/queue', '/vehicles', '/routes', '/trips',
                '/letters', '/ordinance', '/models', '/service_calls', '/rooms', '/listing_calls', '/attending', '/call', '/panel', '/errorlogs',
                '/qrcodelogs', '/perfis', '/paginas-sistema', '/auditoria', '/estabelecimentos', '/alvaras', '/vigilancia/configuracoes',
                '/pharmacy/medicines', '/pharmacy/daily-status', '/pharmacy/monthly-acquisitions', '/pharmacy/compliance',
            ],
            'manager' => [
                '/dashboard', '/clients', '/client_report', '/laboratorio/exames', '/laboratorio/pedidos', '/laboratorio/resultados',
                '/laboratorio/categorias', '/laboratorio/medicos', '/laboratorio/agenda', '/queue', '/trips', '/letters', '/ordinance',
                '/estabelecimentos', '/alvaras', '/pharmacy/medicines', '/pharmacy/daily-status', '/pharmacy/monthly-acquisitions', '/pharmacy/compliance',
            ],
            'user' => ['/clients', '/laboratorio/pedidos', '/laboratorio/resultados', '/laboratorio/agenda', '/queue'],
            'tfd' => ['/clients', '/client_report', '/vehicles', '/routes', '/trips', '/letters', '/ordinance', '/queue'],
            'driver' => ['/panel', '/trips'],
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

                DB::table('profile_page_permissions')->insertOrIgnore([
                    'access_profile_id' => $profile->id,
                    'system_page_id' => $page->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
