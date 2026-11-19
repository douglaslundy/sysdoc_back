<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileSpecialityPermissionRequest;
use App\Models\AccessProfile;
use App\Models\ProfileSpecialityPermission;
use App\Models\Speciality;
use Illuminate\Support\Facades\DB;

/** Acesso do PERFIL às filas por especialidade (ver, editar, inserir paciente). */
class AccessProfileSpecialityPermissionController extends Controller
{
    public function show(AccessProfile $accessProfile)
    {
        return response()->json($this->currentPermissions($accessProfile));
    }

    public function update(UpdateProfileSpecialityPermissionRequest $request, AccessProfile $accessProfile)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $accessProfile) {
            // Só apaga as linhas das especialidades visíveis na secretaria ativa (o escopo do model cuida disso).
            ProfileSpecialityPermission::where('access_profile_id', $accessProfile->id)->delete();

            foreach ($data['permissions'] as $item) {
                $canEdit = (bool) ($item['can_edit'] ?? false);
                $canInsert = (bool) ($item['can_insert'] ?? false);
                $canView = (bool) ($item['can_view'] ?? false) || $canEdit || $canInsert;

                if (! $canView) {
                    continue;
                }

                ProfileSpecialityPermission::create([
                    'access_profile_id' => $accessProfile->id,
                    'speciality_id' => $item['speciality_id'],
                    'can_view' => $canView,
                    'can_edit' => $canEdit,
                    'can_insert' => $canInsert,
                ]);
            }
        });

        return response()->json($this->currentPermissions($accessProfile));
    }

    private function currentPermissions(AccessProfile $profile): array
    {
        $specialities = Speciality::orderBy('name')->get(['id', 'name']);
        $existing = ProfileSpecialityPermission::where('access_profile_id', $profile->id)->get()->keyBy('speciality_id');

        return $specialities->map(function ($speciality) use ($existing) {
            $perm = $existing->get($speciality->id);

            return [
                'speciality_id' => $speciality->id,
                'speciality_name' => $speciality->name,
                'can_view' => (bool) ($perm->can_view ?? false),
                'can_edit' => (bool) ($perm->can_edit ?? false),
                'can_insert' => (bool) ($perm->can_insert ?? false),
            ];
        })->values()->all();
    }
}
