<?php

namespace App\Http\Controllers;

use App\Http\Requests\PharmacyCatalogUpsertRequest;
use App\Services\Pharmacy\PharmacyCatalogAdminService;
use Illuminate\Http\JsonResponse;

class PharmacyCatalogAdminController extends Controller
{
    public function __construct(private PharmacyCatalogAdminService $service)
    {
    }

    public function index(string $type): JsonResponse
    {
        return response()->json($this->service->list($type));
    }

    public function store(PharmacyCatalogUpsertRequest $request, string $type): JsonResponse
    {
        return response()->json($this->service->store($type, $request->validated()), 201);
    }

    public function update(PharmacyCatalogUpsertRequest $request, string $type, int $id): JsonResponse
    {
        return response()->json($this->service->update($type, $id, $request->validated()));
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        $this->service->destroy($type, $id);

        return response()->json(['message' => 'Registro do catálogo removido com sucesso.']);
    }
}
