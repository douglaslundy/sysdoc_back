<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListMedicineMonthlyAcquisitionsRequest;
use App\Http\Requests\UpsertMedicineMonthlyAcquisitionRequest;
use App\Http\Resources\MedicineMonthlyAcquisitionResource;
use App\Services\Pharmacy\MedicineMonthlyAcquisitionService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MedicineMonthlyAcquisitionController extends Controller
{
    public function __construct(private MedicineMonthlyAcquisitionService $service)
    {
    }

    public function index(ListMedicineMonthlyAcquisitionsRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 20);

        return MedicineMonthlyAcquisitionResource::collection($this->service->paginate($request->validated(), $perPage));
    }

    public function store(UpsertMedicineMonthlyAcquisitionRequest $request): JsonResponse
    {
        $acquisition = $this->service->upsert($request->validated(), auth()->id());

        return response()->json(new MedicineMonthlyAcquisitionResource($acquisition));
    }

    public function update(UpsertMedicineMonthlyAcquisitionRequest $request, int $id): JsonResponse
    {
        try {
            $acquisition = $this->service->update($id, $request->validated(), auth()->id());

            return response()->json(new MedicineMonthlyAcquisitionResource($acquisition));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(['message' => 'Aquisição mensal removida com sucesso.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
