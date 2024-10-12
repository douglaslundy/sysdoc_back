<?php

namespace App\Http\Controllers;

use App\Models\Trip;
use Illuminate\Http\Request;

class TripController extends Controller
{
    public function index()
    {
        return Trip::with(['driver', 'vehicle', 'route', 'clients'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            'route_id' => 'required|exists:routes,id',
            'departure_time' => 'required|date',
            'client_ids' => 'array|exists:clients,id'
        ]);

        $trip = Trip::create($validated);
        $trip->clients()->sync($request->client_ids);

        return response()->json($trip->load('clients'), 201);
    }

    public function show($id)
    {
        return Trip::with(['driver', 'vehicle', 'route', 'clients'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $trip = Trip::findOrFail($id);
        $trip->update($request->only(['user_id', 'vehicle_id', 'route_id', 'departure_time']));
        $trip->clients()->sync($request->client_ids);

        return response()->json($trip->load('clients'));
    }

    public function destroy($id)
    {
        $trip = Trip::findOrFail($id);
        $trip->delete();

        return response()->json(null, 204);
    }
}
