<?php

namespace App\Http\Controllers;

use App\Http\Requests\ListMedicinePublicationsRequest;
use App\Http\Requests\StoreMedicinePublicationRequest;
use App\Http\Resources\MedicinePublicationResource;
use App\Services\Pharmacy\MedicinePublicationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MedicinePublicationController extends Controller
{
    public function __construct(private MedicinePublicationService $service)
    {
    }

    public function index(ListMedicinePublicationsRequest $request): AnonymousResourceCollection
    {
        $perPage = (int) ($request->validated()['per_page'] ?? 20);

        return MedicinePublicationResource::collection($this->service->paginate($request->validated(), $perPage));
    }

    public function store(StoreMedicinePublicationRequest $request): JsonResponse
    {
        try {
            $publication = $this->service->create($request->validated(), auth()->id());

            return response()->json(new MedicinePublicationResource($publication), 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(['message' => 'Publicação removida com sucesso.']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }
}
