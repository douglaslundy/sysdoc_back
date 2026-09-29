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
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort_by' => ['nullable', 'string', 'in:done_at,date_of_realized,id'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
