<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class ListQueuesRequest extends BaseApiFormRequest
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
            'search' => ['nullable', 'string', 'max:120'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'done' => ['nullable', 'integer', 'in:0,1,2'],
            'urgency' => ['nullable', 'integer', 'in:0,1,2'],
            'speciality_id' => ['nullable', 'integer', 'exists:specialities,id'],
        ];
    }
}
