<?php

namespace Tests\Feature;

use App\Models\MedicineItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaObserverFarmaciaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_medicine_item_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $medicine = MedicineItem::create([
            'internal_code' => 'CODE-001',
            'brand_name' => 'Dipirona 500mg',
            'active_ingredient' => 'Dipirona',
            'concentration' => '500mg',
            'pharmaceutical_form' => 'Comprimido',
            'presentation' => '30 comprimidos',
            'unit_measure' => 'Blister',
            'active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'MedicineItem',
            'model_id'   => $medicine->id,
            'user_id'    => $this->admin->id,
        ]);
    }

    public function test_medicine_item_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $medicine = MedicineItem::create([
            'internal_code' => 'CODE-002',
            'brand_name' => 'Dipirona 500mg',
            'active_ingredient' => 'Dipirona',
            'concentration' => '500mg',
            'pharmaceutical_form' => 'Comprimido',
            'presentation' => '30 comprimidos',
            'unit_measure' => 'Blister',
            'active' => true,
        ]);

        $medicine->update(['brand_name' => 'Dipirona 1g']);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'MedicineItem',
            'model_id'   => $medicine->id,
        ]);
    }

    public function test_medicine_item_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $medicine = MedicineItem::create([
            'internal_code' => 'CODE-003',
            'brand_name' => 'Dipirona 500mg',
            'active_ingredient' => 'Dipirona',
            'concentration' => '500mg',
            'pharmaceutical_form' => 'Comprimido',
            'presentation' => '30 comprimidos',
            'unit_measure' => 'Blister',
            'active' => true,
        ]);

        $id = $medicine->id;
        $medicine->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'MedicineItem',
            'model_id'   => $id,
        ]);
    }
}
