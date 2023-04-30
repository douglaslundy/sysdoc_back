<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CallService;

class CallServiceController extends Controller
{
    public function index()
    {
        $callServices = CallService::all();
        return response()->json($callServices);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'required|string|max:200'
        ]);

        $callService = CallService::create([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json($callService, 201);
    }

    /**
     * @param  CallService  $callService
     */
    public function show(CallService $callService)
    {
        return response()->json($callService);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'description' => 'required|string|max:200'
        ]);

        $callService = CallService::find($id);

        $callService->update([
            'name' => $request->name,
            'description' => $request->description
        ]);

        return response()->json($callService);
    }

    public function destroy($id)
    {
        $callService = CallService::find($id);
        $callService->delete();

        return response()->json(null, 204);
    }
}
