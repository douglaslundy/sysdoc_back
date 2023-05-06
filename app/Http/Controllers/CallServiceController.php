<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallServicelRequest;
use Illuminate\Http\Request;
use App\Models\CallService;

class CallServiceController extends Controller
{
    public function index()
    {
        $callServices = CallService::all();
        return response()->json($callServices);
    }

    public function store(StoreCallServicelRequest $request)
    {
        $callService = new CallService;
        $callService->name = $request->input('name');
        $callService->description = $request->input('description');
        $callService->save();

        return response()->json([
            'message' => 'Call service created successfully!',
            'call_service' => $callService
        ], 201);
    }

    /**
     * @param  CallService  $callService
     */
    public function show(CallService $callService)
    {
        return response()->json($callService);
    }

    public function update(StoreCallServicelRequest $request, $id)
    {
        $callService = callService::find($id);
        if (!$callService) {
            return response()->json([
                'error' => 'Room not found'
            ], 404);
        }
        $callService->name = $request->input('name');
        $callService->description = $request->input('description');
        $callService->save();

        return response()->json([
            'message' => 'Call service updated successfully!',
            'call_service' => $callService
        ]);
    }

    public function destroy($id)
    {
        $callService = CallService::find($id);
        $callService->delete();

        return response()->json(null, 204);
    }
}
