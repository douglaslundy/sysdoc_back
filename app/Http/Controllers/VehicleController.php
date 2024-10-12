<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVehicleRequest;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
    public function index()
    {
        return Vehicle::where('active', true)->get();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreVehicleRequest $request)
    {
        DB::beginTransaction();
        try {

            $array = ['status' => 'created'];
            $vehicle = Vehicle::create($request->all());
            $array['vehicle'] = $vehicle;
            DB::commit();

            return response()->json($array, 201);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show($id)
    {
        return Vehicle::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Vehicle  $vehicle
     * @return \Illuminate\Http\Response
     */
    public function update(StoreVehicleRequest $request, Vehicle  $vehicle)
    {
        DB::beginTransaction();
        try {
            $array = ['status' => 'updated'];
            $vehicle->update($request->all());
            $array['vehicle'] = $vehicle;

            DB::commit();

            return response()->json([
                'message' => 'Vehicle updated successfully!',
                'vehicle' => $vehicle
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->active = false;
        $vehicle->update();

        return response()->json(null, 204);
    }
}
