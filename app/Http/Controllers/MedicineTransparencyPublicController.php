<?php

namespace App\Http\Controllers;

use App\Http\Requests\PublicMedicineDailyRequest;
use App\Http\Requests\PublicMedicineMonthlyRequest;
use App\Services\MedicineTransparencyService;
use Illuminate\Http\JsonResponse;

class MedicineTransparencyPublicController extends Controller
{
    public function __construct(private MedicineTransparencyService $service)
    {
    }

    public function daily(PublicMedicineDailyRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->getPublicDailyList($request->validated('date'))
        );
    }

    public function panel(PublicMedicineDailyRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->getPublicDailyList($request->validated('date'))
        );
    }

    public function monthly(PublicMedicineMonthlyRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->getPublicMonthlyAcquisitions($request->validated('month'))
        );
    }
}
