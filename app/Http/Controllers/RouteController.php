<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRouteRequest;
use App\Models\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RouteController extends Controller
{
    public function index()
    {
        return Route::with('user')->where('active', true)->get();
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRouteRequest $request)
    {
        DB::beginTransaction();
        try {

            $array = ['status' => 'created'];
            $route = Route::create($request->all());
            $array['route'] = $route;
            DB::commit();
            return response()->json($array, 201);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function show($id)
    {
        return Route::findOrFail($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Route  $vehicle
     * @return \Illuminate\Http\Response
     */
    public function update(StoreRouteRequest $request, Route  $route)
    {
        DB::beginTransaction();
        try {
            $array = ['status' => 'updated'];
            $route->update($request->all());
            $array['route'] = $route;

            DB::commit();

            return response()->json([
                'message' => 'Route updated successfully!',
                'route' => $route
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function destroy($id)
    {
        $route = Route::findOrFail($id);
        $route->active = false;
        $route->update();

        return response()->json(null, 204);
    }
}
