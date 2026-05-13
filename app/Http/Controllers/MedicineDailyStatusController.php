<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListMedicineDailyStatusesRequest;
use App\Http\Requests\UpsertMedicineDailyStatusRequest;
use App\Http\Resources\MedicineDailyStatusResource;
use App\Services\Pharmacy\MedicineDailyStatusService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MedicineDailyStatusController extends Controller
{
    public function __construct(private MedicineDailyStatusService $service)
    {
    }

    public function index(ListMedicineDailyStatusesRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 20);

        return MedicineDailyStatusResource::collection($this->service->paginate($request->validated(), $perPage));
    }

    public function store(UpsertMedicineDailyStatusRequest $request): JsonResponse
    {
        $status = $this->service->upsert($request->validated(), auth()->id());

        return response()->json(new MedicineDailyStatusResource($status));
    }

    public function update(UpsertMedicineDailyStatusRequest $request, int $id): JsonResponse
    {
        try {
            $status = $this->service->update($id, $request->validated(), auth()->id());

            return response()->json(new MedicineDailyStatusResource($status));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(['message' => 'Status diário removido com sucesso.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
