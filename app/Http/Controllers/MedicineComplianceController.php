<?php

namespace App\Http\Controllers;

use App\Http\Requests\MedicineComplianceIndexRequest;
use App\Http\Resources\MedicineComplianceResource;
use App\Services\Pharmacy\MedicineComplianceService;
use Illuminate\Http\JsonResponse;

class MedicineComplianceController extends Controller
{
    public function __construct(private MedicineComplianceService $service)
    {
    }

    public function index(MedicineComplianceIndexRequest $request): JsonResponse
    {
        return response()->json(new MedicineComplianceResource($this->service->summary()));
    }
}
