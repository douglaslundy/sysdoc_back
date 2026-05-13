<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListMedicineItemsRequest;
use App\Http\Requests\SelectMedicineItemsRequest;
use App\Http\Requests\StoreMedicineItemRequest;
use App\Http\Requests\UpdateMedicineItemRequest;
use App\Http\Resources\MedicineItemResource;
use App\Services\Pharmacy\MedicineItemService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MedicineItemController extends Controller
{
    public function __construct(private MedicineItemService $service)
    {
    }

    public function index(ListMedicineItemsRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 15);

        return MedicineItemResource::collection($this->service->paginate($request->validated(), $perPage));
    }

    public function select(SelectMedicineItemsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = $this->service->queryForSelect($validated);
        $limit = (int) ($validated['limit'] ?? 300);

        return response()->json(
            $query->limit($limit)->get(['id', 'internal_code', 'active_ingredient', 'active'])
        );
    }

    public function store(StoreMedicineItemRequest $request): JsonResponse
    {
        $medicine = $this->service->create($request->validated());

        return response()->json(new MedicineItemResource($medicine), 201);
    }

    public function show(int $id): JsonResponse
    {
        try {
            $medicine = $this->service->show($id);

            return response()->json(new MedicineItemResource($medicine));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function update(UpdateMedicineItemRequest $request, int $id): JsonResponse
    {
        try {
            $medicine = $this->service->update($id, $request->validated());

            return response()->json(new MedicineItemResource($medicine));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(['message' => 'Medicamento removido com sucesso.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
