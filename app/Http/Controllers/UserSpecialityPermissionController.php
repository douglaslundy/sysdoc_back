<?php

namespace App\Http\Controllers;

use App\Models\Speciality;
use App\Models\User;
use App\Models\UserSpecialityPermission;
use Illuminate\Http\Request;

class UserSpecialityPermissionController extends Controller
{
    public function show(User $user)
    {
        return response()->json($this->currentPermissions($user));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'permissions' => ['present', 'array'],
            'permissions.*.speciality_id' => ['required', 'integer', 'exists:specialities,id'],
            'permissions.*.can_view' => ['boolean'],
            'permissions.*.can_edit' => ['boolean'],
            'permissions.*.can_insert' => ['boolean'],
        ]);

        UserSpecialityPermission::where('user_id', $user->id)->delete();

        foreach ($data['permissions'] as $item) {
            $canEdit = (bool) ($item['can_edit'] ?? false);
            $canInsert = (bool) ($item['can_insert'] ?? false);
            $canView = (bool) ($item['can_view'] ?? false) || $canEdit || $canInsert;

            if (! $canView && ! $canEdit && ! $canInsert) {
                continue;
            }

            UserSpecialityPermission::create([
                'user_id' => $user->id,
                'speciality_id' => $item['speciality_id'],
                'can_view' => $canView,
                'can_edit' => $canEdit,
                'can_insert' => $canInsert,
            ]);
        }

        return response()->json($this->currentPermissions($user));
    }

    private function currentPermissions(User $user): array
    {
        $specialities = Speciality::orderBy('name')->get(['id', 'name']);
        $existing = UserSpecialityPermission::where('user_id', $user->id)->get()->keyBy('speciality_id');

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
