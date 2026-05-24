<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class UpdateQueueRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/queue');
    }

    public function rules(): array
    {
        return [
            'id_client' => ['sometimes', 'required', 'exists:clients,id'],
            'id_specialities' => ['sometimes', 'required', 'exists:specialities,id'],
            'id_user' => ['sometimes', 'required', 'exists:users,id'],
            'done' => ['boolean'],
            'date_of_realized' => ['nullable', 'date'],
            'urgency' => ['sometimes', 'required', 'boolean'],
            'obs' => ['nullable', 'string', 'max:200'],
        ];
    }
}
