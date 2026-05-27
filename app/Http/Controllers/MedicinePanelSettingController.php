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
        $data = $request->validated();

        if ($data['filter_show_all']) {
            $data = array_merge($data, [
                'filter_is_free_distribution' => true,
                'filter_is_controlled' => true,
                'filter_is_judicial_order' => true,
                'filter_is_high_cost' => true,
                'filter_active' => true,
            ]);
        }

        $setting->update($data);

        return response()->json($setting);
    }
}
