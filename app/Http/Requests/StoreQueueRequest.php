<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use App\Services\Authorization\SpecialityPermissionService;

class StoreQueueRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! app(PagePermissionService::class)->canAccess($user, '/queue')) {
            return false;
        }

        $specialityId = (int) $this->input('id_specialities');

        if ($specialityId <= 0) {
            return true;
        }

        return app(SpecialityPermissionService::class)->canInsert($user, $specialityId);
    }

    public function rules(): array
    {
        $sid = app(\App\Services\Secretarias\ActiveSecretaria::class)->forCreate($this->input('secretaria_id')) ?? 0;

        return [
            'secretaria_id' => \App\Support\EscopoAlmoxarifado::regraCampo(),
            'id_client' => ['required', 'exists:clients,id'],
            'id_specialities' => ['required', 'integer', \App\Support\EscopoAlmoxarifado::existe('specialities', $sid)],
            'id_user' => ['required', 'exists:users,id'],
            'done' => ['boolean'],
            'date_of_realized' => ['nullable', 'date'],
            'urgency' => ['required', 'boolean'],
            'obs' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
