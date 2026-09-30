<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\SystemPage;
use App\Models\User;
use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Matriz rota x página: a tabela central config/route_permissions.php decide quem pode chamar
 * cada endpoint. Antes, 305 rotas exigiam só login.
 */
class RoutePagePolicyTest extends TestCase
{
    use RefreshDatabase;

    private const MENSAGEM = 'Usuário sem permissão para esta página.';

    private function userWithPages(array $paths, string $slug = 'perfil-teste'): User
    {
        $profile = AccessProfile::firstOrCreate(['slug' => $slug], ['nome' => $slug, 'ativo' => true]);
        $ids = [];
        foreach ($paths as $path) {
            $ids[] = SystemPage::firstOrCreate(['path' => $path], ['titulo' => $path, 'ativo' => true])->id;
        }
        $profile->pages()->sync($ids);

        return User::factory()->create(['profile' => $slug, 'active' => true]);
    }

    private function req(User $user, string $method, string $uri)
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($user, 'sanctum')->json($method, $uri, []);
    }

    // ---- curinga de página ----

    public function test_curinga_de_pagina_libera_qualquer_pagina_do_modulo_e_nao_outros_modulos(): void
    {
        $service = app(PagePermissionService::class);
        $user = $this->userWithPages(['/laboratorio/pedidos']);

        $this->assertTrue($service->canAccessAny($user, ['/laboratorio*']));
        $this->assertFalse($service->canAccessAny($user, ['/laboratorioX*']));
        $this->assertFalse($service->canAccessAny($user, ['/almoxarifado*']));
        $this->assertFalse($service->canAccessAny($user, ['/lab*']), 'O curinga respeita a fronteira de "/".');
    }

    // ---- matriz ----

    /** @return array<string, array{0: string, 1: string, 2: string}> */
    public static function rotasProtegidas(): array
    {
        return [
            'laboratorio pedidos' => ['GET', '/api/laboratorio/pedidos', '/laboratorio/pedidos'],
            'laboratorio exames' => ['GET', '/api/laboratorio/exames', '/laboratorio/exames'],
            'laboratorio escrita' => ['POST', '/api/laboratorio/categorias', '/laboratorio/categorias'],
            'atendimento fila' => ['GET', '/api/attendance/queue', '/attendance/queue'],
            'atendimento salas admin' => ['GET', '/api/attendance/rooms-admin', '/attendance/rooms'],
            'almoxarifado produtos' => ['GET', '/api/almoxarifado/produtos', '/almoxarifado/produtos'],
            'almoxarifado requisicoes' => ['GET', '/api/almoxarifado/requisicoes', '/almoxarifado/requisicoes'],
            'farmacia catalogos' => ['GET', '/api/pharmacy/catalogs', '/pharmacy/medicines'],
            'farmacia medicamentos' => ['GET', '/api/medicines', '/pharmacy/medicines'],
            'oficios' => ['GET', '/api/letters', '/letters'],
            'portarias' => ['GET', '/api/ordinances', '/ordinance'],
            'modelos' => ['GET', '/api/models', '/models'],
            'kanban' => ['GET', '/api/kanban', '/kanban'],
            'qrcode logs' => ['GET', '/api/qrcode-logs', '/qrcodelogs'],
            'planos de tratamento' => ['GET', '/api/queue-treatment-plans', '/queue'],
            'conformidade cidadao' => ['GET', '/api/conformidade-cidadao/historico', '/conformidade-cidadao'],
            'fiscalizacoes leitura' => ['GET', '/api/fiscalizacoes', '/fiscalizacoes'],
            'estabelecimentos por fiscalizacao' => ['GET', '/api/estabelecimentos/select', '/fiscalizacoes'],
            'alvaras leitura' => ['GET', '/api/alvaras', '/alvaras'],
            'viagens' => ['GET', '/api/trips', '/trips'],
            'viagens do cidadao' => ['GET', '/api/trips', '/clients'],
            'veiculos leitura pela viagem' => ['GET', '/api/vehicles', '/trips'],
            'veiculos escrita' => ['POST', '/api/vehicles', '/vehicles'],
            'especialidades escrita' => ['POST', '/api/specialities', '/specialities'],
            'protocolo caixa de entrada' => ['GET', '/api/protocolos/caixa-entrada', '/protocolo/caixa-entrada'],
            'protocolo pelo kanban' => ['GET', '/api/protocolos/1', '/kanban'],
            'avisos do sistema (admin de avisos)' => ['GET', '/api/system-notices', '/avisos'],
        ];
    }

    /** @dataProvider rotasProtegidas */
    public function test_usuario_sem_a_pagina_recebe_403_e_com_a_pagina_passa(string $method, string $uri, string $page): void
    {
        $semPagina = User::factory()->create(['profile' => 'user', 'active' => true]);
        $response = $this->req($semPagina, $method, $uri)->assertForbidden();
        $this->assertSame(self::MENSAGEM, $response->json('message'), "$method $uri deveria ser barrada pela tabela de páginas");

        $comPagina = $this->userWithPages([$page]);
        $this->assertNotSame(403, $this->req($comPagina, $method, $uri)->status(), "$method $uri deveria liberar quem tem $page");

        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->assertNotSame(403, $this->req($admin, $method, $uri)->status());
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function rotasAbertas(): array
    {
        return [
            'especialidades (lista usada em vários módulos)' => ['GET', '/api/specialities'],
            'avisos ativos (todo usuário vê)' => ['GET', '/api/system-notices/active'],
            'tipos de protocolo (formulários)' => ['GET', '/api/protocolos/tipos'],
            'unidades (formulários)' => ['GET', '/api/protocolos/unidades-organizacionais'],
            'contexto do novo protocolo' => ['GET', '/api/protocolos/contexto-novo'],
            'estados' => ['GET', '/api/states'],
            'minhas permissões' => ['GET', '/api/auth/my-permissions'],
        ];
    }

    /** @dataProvider rotasAbertas */
    public function test_rotas_de_apoio_continuam_abertas_a_qualquer_usuario_logado(string $method, string $uri): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        $this->req($user, $method, $uri)->assertOk();
    }

    public function test_rotas_legadas_de_chamadas_so_para_administrador(): void
    {
        $user = User::factory()->create(['profile' => 'user', 'active' => true]);

        foreach (['/api/rooms', '/api/calls', '/api/services', '/api/endedcalls', '/api/sectors'] as $uri) {
            $this->req($user, 'GET', $uri)->assertForbidden();
        }
    }

    public function test_pagina_de_outro_modulo_nao_libera(): void
    {
        $user = $this->userWithPages(['/almoxarifado/produtos']);

        $this->req($user, 'GET', '/api/laboratorio/pedidos')->assertForbidden();
        $this->req($user, 'GET', '/api/fiscalizacoes')->assertForbidden();
    }
}
