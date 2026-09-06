<?php

namespace Tests\Feature;

use App\Models\Speciality;
use App\Models\User;
use App\Models\UserSpecialityPermission;
use App\Services\Authorization\SpecialityPermissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecialityPermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SpecialityPermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SpecialityPermissionService();
    }

    public function test_admin_tem_acesso_total_sem_nenhuma_linha_de_permissao(): void
    {
        $admin = User::factory()->create(['profile' => 'admin']);
        $speciality = Speciality::create(['id_user' => $admin->id, 'name' => 'Cardiologia']);

        $this->assertTrue($this->service->canView($admin, $speciality->id));
        $this->assertTrue($this->service->canEdit($admin, $speciality->id));
        $this->assertTrue($this->service->canInsert($admin, $speciality->id));
        $this->assertNull($this->service->viewableSpecialityIds($admin));
    }

    public function test_usuario_comum_sem_linha_nao_tem_nenhum_acesso(): void
    {
        $admin = User::factory()->create(['profile' => 'admin']);
        $user = User::factory()->create(['profile' => 'user']);
        $speciality = Speciality::create(['id_user' => $admin->id, 'name' => 'Fisioterapia']);

        $this->assertFalse($this->service->canView($user, $speciality->id));
        $this->assertFalse($this->service->canEdit($user, $speciality->id));
        $this->assertFalse($this->service->canInsert($user, $speciality->id));
        $this->assertSame([], $this->service->viewableSpecialityIds($user));
    }

    public function test_usuario_comum_com_linha_parcial_so_tem_a_permissao_concedida(): void
    {
        $admin = User::factory()->create(['profile' => 'admin']);
        $user = User::factory()->create(['profile' => 'user']);
        $speciality = Speciality::create(['id_user' => $admin->id, 'name' => 'Fonoaudiologia']);

        UserSpecialityPermission::create([
            'user_id' => $user->id,
            'speciality_id' => $speciality->id,
            'can_view' => true,
            'can_edit' => false,
            'can_insert' => false,
        ]);

        $this->assertTrue($this->service->canView($user, $speciality->id));
        $this->assertFalse($this->service->canEdit($user, $speciality->id));
        $this->assertFalse($this->service->canInsert($user, $speciality->id));
        $this->assertSame([$speciality->id], $this->service->viewableSpecialityIds($user));
    }
}
