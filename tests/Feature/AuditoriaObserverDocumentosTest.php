<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Models\Ordinance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaObserverDocumentosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_letter_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $letter = Letter::create([
            'id_user'       => $this->admin->id,
            'number'        => 1,
            'subject_matter' => 'Ofício de Teste',
            'sender'        => 'Secretaria',
            'recipient'     => 'Diretor',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'Letter',
            'model_id'   => $letter->id,
        ]);
    }

    public function test_letter_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $letter = Letter::create([
            'id_user'       => $this->admin->id,
            'number'        => 1,
            'subject_matter' => 'Ofício de Teste',
            'sender'        => 'Secretaria',
            'recipient'     => 'Diretor',
        ]);

        $letter->subject_matter = 'Ofício Atualizado';
        $letter->save();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'Letter',
            'model_id'   => $letter->id,
        ]);
    }

    public function test_letter_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $letter = Letter::create([
            'id_user'       => $this->admin->id,
            'number'        => 1,
            'subject_matter' => 'Ofício de Teste',
            'sender'        => 'Secretaria',
            'recipient'     => 'Diretor',
        ]);
        $id = $letter->id;
        $letter->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'Letter',
            'model_id'   => $id,
        ]);
    }

    public function test_ordinance_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $ordinance = Ordinance::create([
            'user_id'       => $this->admin->id,
            'number'        => 1,
            'year'          => (int) date('Y'),
            'type'          => 'normativa',
            'title'         => 'Portaria de Teste',
            'subject'       => 'Teste de Portaria',
            'signatory_name' => 'Secretário',
            'publication_date' => now()->toDateString(),
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'Ordinance',
            'model_id'   => $ordinance->id,
        ]);
    }

    public function test_ordinance_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $ordinance = Ordinance::create([
            'user_id'       => $this->admin->id,
            'number'        => 1,
            'year'          => (int) date('Y'),
            'type'          => 'normativa',
            'title'         => 'Portaria de Teste',
            'subject'       => 'Teste de Portaria',
            'signatory_name' => 'Secretário',
            'publication_date' => now()->toDateString(),
        ]);

        $ordinance->title = 'Portaria Atualizada';
        $ordinance->save();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'Ordinance',
            'model_id'   => $ordinance->id,
        ]);
    }

    public function test_ordinance_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $ordinance = Ordinance::create([
            'user_id'       => $this->admin->id,
            'number'        => 1,
            'year'          => (int) date('Y'),
            'type'          => 'normativa',
            'title'         => 'Portaria de Teste',
            'subject'       => 'Teste de Portaria',
            'signatory_name' => 'Secretário',
            'publication_date' => now()->toDateString(),
        ]);
        $id = $ordinance->id;
        $ordinance->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'Ordinance',
            'model_id'   => $id,
        ]);
    }
}
