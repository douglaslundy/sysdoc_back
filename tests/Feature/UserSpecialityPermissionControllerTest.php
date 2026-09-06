<?php

namespace Tests\Feature;

use App\Models\Speciality;
use App\Models\User;
use App\Models\UserSpecialityPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSpecialityPermissionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $target;

    private Speciality $fisio;

    private Speciality $fono;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        $this->target = User::factory()->create(['profile' => 'user', 'active' => true]);
        $this->fisio = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fisioterapia']);
        $this->fono = Speciality::create(['id_user' => $this->admin->id, 'name' => 'Fonoaudiologia']);
    }

    public function test_admin_ve_todas_as_especialidades_com_flags_zeradas_por_padrao(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/users/{$this->target->id}/speciality-permissions");

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonFragment([
            'speciality_id' => $this->fisio->id,
            'speciality_name' => 'Fisioterapia',
            'can_view' => false,
            'can_edit' => false,
            'can_insert' => false,
        ]);
    }

    public function test_admin_grava_permissoes_e_can_edit_forca_can_view(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/users/{$this->target->id}/speciality-permissions", [
                'permissions' => [
                    ['speciality_id' => $this->fisio->id, 'can_view' => false, 'can_edit' => true, 'can_insert' => false],
                    ['speciality_id' => $this->fono->id, 'can_view' => false, 'can_edit' => false, 'can_insert' => false],
                ],
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('user_speciality_permissions', [
            'user_id' => $this->target->id,
            'speciality_id' => $this->fisio->id,
            'can_view' => 1,
            'can_edit' => 1,
            'can_insert' => 0,
        ]);

        $this->assertDatabaseMissing('user_speciality_permissions', [
            'user_id' => $this->target->id,
            'speciality_id' => $this->fono->id,
        ]);
    }

    public function test_atualizar_substitui_permissoes_anteriores(): void
    {
        UserSpecialityPermission::create([
            'user_id' => $this->target->id,
            'speciality_id' => $this->fono->id,
            'can_view' => true,
            'can_edit' => true,
            'can_insert' => true,
        ]);

        $this->actingAs($this->admin, 'sanctum')
            ->putJson("/api/users/{$this->target->id}/speciality-permissions", [
                'permissions' => [
                    ['speciality_id' => $this->fisio->id, 'can_view' => true, 'can_edit' => false, 'can_insert' => false],
                ],
            ])->assertOk();

        $this->assertDatabaseHas('user_speciality_permissions', [
            'user_id' => $this->target->id,
            'speciality_id' => $this->fisio->id,
        ]);
        $this->assertDatabaseMissing('user_speciality_permissions', [
            'user_id' => $this->target->id,
            'speciality_id' => $this->fono->id,
        ]);
    }

    public function test_usuario_nao_admin_recebe_403(): void
    {
        $this->actingAs($this->target, 'sanctum')
            ->getJson("/api/users/{$this->target->id}/speciality-permissions")
            ->assertStatus(403);

        $this->actingAs($this->target, 'sanctum')
            ->putJson("/api/users/{$this->target->id}/speciality-permissions", ['permissions' => []])
            ->assertStatus(403);
    }
}
