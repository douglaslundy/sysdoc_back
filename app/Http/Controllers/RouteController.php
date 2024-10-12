<?php

namespace App\Http\Controllers;

use App\Models\Route;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function index()
    {
        return Route::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'distance' => 'required|integer|min:1',
        ]);

        $route = Route::create($validated);

        return response()->json($route, 201);
    }

    public function show($id)
    {
        return Route::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $route = Route::findOrFail($id);

        $validated = $request->validate([
            'origin' => 'sometimes|string|max:255',
            'destination' => 'sometimes|string|max:255',
            'distance' => 'sometimes|integer|min:1',
        ]);

        $route->update($validated);

        return response()->json($route);
    }

    public function destroy($id)
    {
        $route = Route::findOrFail($id);
        $route->delete();

        return response()->json(null, 204);
    }
}
