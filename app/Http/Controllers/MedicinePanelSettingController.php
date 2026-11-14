<?php

namespace App\Http\Controllers;

use App\Http\Requests\ShowMedicinePanelSettingRequest;
use App\Http\Requests\UpdateMedicinePanelSettingRequest;
use App\Models\PharmacyMedicinePanelSetting;
use App\Support\EscopoAlmoxarifado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicinePanelSettingController extends Controller
{
    public function show(ShowMedicinePanelSettingRequest $request): JsonResponse
    {
        return response()->json(PharmacyMedicinePanelSetting::current(EscopoAlmoxarifado::id($request->input('secretaria_id'))));
    }

    public function update(UpdateMedicinePanelSettingRequest $request): JsonResponse
    {
        $setting = PharmacyMedicinePanelSetting::current(EscopoAlmoxarifado::id($request->input('secretaria_id')));
        $data = $request->safe()->except('secretaria_id');

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
