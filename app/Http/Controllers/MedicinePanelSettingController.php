<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMedicinePanelSettingRequest;
use App\Models\PharmacyMedicinePanelSetting;
use Illuminate\Http\JsonResponse;

class MedicinePanelSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json(PharmacyMedicinePanelSetting::current());
    }

    public function update(UpdateMedicinePanelSettingRequest $request): JsonResponse
    {
        $setting = PharmacyMedicinePanelSetting::current();
        $setting->update($request->validated());

        return response()->json($setting);
    }
}
