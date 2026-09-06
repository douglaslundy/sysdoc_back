<?php

namespace Tests\Feature;

use App\Models\AccessProfile;
use App\Models\Speciality;
use App\Models\SystemPage;
use App\Models\User;
use App\Services\Authorization\UserSpecialityPermissionBackfiller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserSpecialityPermissionBackfillTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_concede_acesso_total_a_usuarios_com_acesso_a_fila(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);

        $profile = AccessProfile::create([
            'nome' => 'Recepção Backfill',
            'slug' => 'recepcao-backfill',
            'ativo' => true,
        ]);
        $page = SystemPage::firstOrCreate(
            ['path' => '/queue'],
            ['titulo' => 'Fila', 'ativo' => true]
        );
        $profile->pages()->attach($page->id);

        $recepcionista = User::factory()->create(['profile' => 'recepcao-backfill', 'active' => true]);
        $semAcessoFila = User::factory()->create(['profile' => 'perfil-sem-fila', 'active' => true]);

        $speciality = Speciality::create(['id_user' => $admin->id, 'name' => 'Fisioterapia']);

        $inserted = (new UserSpecialityPermissionBackfiller())->run();

        $this->assertGreaterThanOrEqual(2, $inserted);

        $this->assertDatabaseHas('user_speciality_permissions', [
            'user_id' => $admin->id,
            'speciality_id' => $speciality->id,
            'can_view' => 1,
            'can_edit' => 1,
            'can_insert' => 1,
        ]);

        $this->assertDatabaseHas('user_speciality_permissions', [
            'user_id' => $recepcionista->id,
            'speciality_id' => $speciality->id,
            'can_view' => 1,
            'can_edit' => 1,
            'can_insert' => 1,
        ]);

        $this->assertDatabaseMissing('user_speciality_permissions', [
            'user_id' => $semAcessoFila->id,
            'speciality_id' => $speciality->id,
        ]);
    }

    public function test_backfill_e_idempotente(): void
    {
        $admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
        Speciality::create(['id_user' => $admin->id, 'name' => 'Fonoaudiologia']);

        $backfiller = new UserSpecialityPermissionBackfiller();
        $first = $backfiller->run();
        $second = $backfiller->run();

        $this->assertGreaterThanOrEqual(1, $first);
        $this->assertSame(0, $second);
    }
}
