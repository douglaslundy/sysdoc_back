<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCallServicelRequest;
use App\Models\CallService;
use App\Services\AuditService;
use Illuminate\Http\Request;

class CallServiceController extends Controller
{
    public function index()
    {
        $callServices = CallService::with(['calls'])->get();
        return response()->json($callServices);
    }

    public function store(StoreCallServicelRequest $request)
    {
        $callService = new CallService;
        $callService->name = $request->input('name');
        $callService->description = $request->input('description');
        $callService->save();
        AuditService::record('CREATE', $callService, null, $callService->toArray());

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
        $old = $callService->toArray();
        $callService->name = $request->input('name');
        $callService->description = $request->input('description');
        $callService->save();
        AuditService::record('UPDATE', $callService, $old, $callService->toArray());

        return response()->json([
            'message' => 'Call service updated successfully!',
            'call_service' => $callService
        ]);
    }

    public function destroy($id)
    {
        $callService = CallService::find($id);
        AuditService::record('DELETE', $callService, $callService->toArray(), null);
        $callService->delete();

        return response()->json(null, 204);
    }
}
