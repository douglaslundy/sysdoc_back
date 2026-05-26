<?php

namespace Tests\Feature;

use App\Models\Vehicle;
use App\Models\Route;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditoriaObserverTfdTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['profile' => 'admin', 'active' => true]);
    }

    public function test_vehicle_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $vehicle = Vehicle::create([
            'brand' => 'Fiat',
            'model' => 'Uno',
            'color' => 'Branco',
            'license_plate' => 'ABC1234',
            'renavan' => '12345678901',
            'chassis' => '12345678901234567',
            'capacity' => 5,
            'year' => 2020,
            'id_user' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'Vehicle',
            'model_id'   => $vehicle->id,
        ]);
    }

    public function test_vehicle_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $vehicle = Vehicle::create([
            'brand' => 'Fiat',
            'model' => 'Uno',
            'color' => 'Branco',
            'license_plate' => 'ABC1234',
            'renavan' => '12345678901',
            'chassis' => '12345678901234567',
            'capacity' => 5,
            'year' => 2020,
            'id_user' => $this->admin->id,
        ]);

        $vehicle->update(['model' => 'Palio']);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'Vehicle',
            'model_id'   => $vehicle->id,
        ]);
    }

    public function test_vehicle_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $vehicle = Vehicle::create([
            'brand' => 'Fiat',
            'model' => 'Uno',
            'color' => 'Branco',
            'license_plate' => 'ABC1234',
            'renavan' => '12345678901',
            'chassis' => '12345678901234567',
            'capacity' => 5,
            'year' => 2020,
            'id_user' => $this->admin->id,
        ]);

        $id = $vehicle->id;
        $vehicle->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'Vehicle',
            'model_id'   => $id,
        ]);
    }

    public function test_route_create_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $route = Route::create([
            'origin' => 'Sao Paulo',
            'origin_state' => 'SP',
            'destination' => 'Rio de Janeiro',
            'destination_state' => 'RJ',
            'distance' => 400,
            'id_user' => $this->admin->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'CREATE',
            'model_type' => 'Route',
            'model_id'   => $route->id,
        ]);
    }

    public function test_route_update_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $route = Route::create([
            'origin' => 'Sao Paulo',
            'origin_state' => 'SP',
            'destination' => 'Rio de Janeiro',
            'destination_state' => 'RJ',
            'distance' => 400,
            'id_user' => $this->admin->id,
        ]);

        $route->update(['distance' => 420]);

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'UPDATE',
            'model_type' => 'Route',
            'model_id'   => $route->id,
        ]);
    }

    public function test_route_delete_grava_audit_log(): void
    {
        $this->actingAs($this->admin, 'sanctum');

        $route = Route::create([
            'origin' => 'Sao Paulo',
            'origin_state' => 'SP',
            'destination' => 'Rio de Janeiro',
            'destination_state' => 'RJ',
            'distance' => 400,
            'id_user' => $this->admin->id,
        ]);

        $id = $route->id;
        $route->delete();

        $this->assertDatabaseHas('audit_logs', [
            'action'     => 'DELETE',
            'model_type' => 'Route',
            'model_id'   => $id,
        ]);
    }
}
