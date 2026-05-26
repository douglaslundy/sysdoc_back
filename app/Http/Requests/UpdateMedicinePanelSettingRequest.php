<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class UpdateMedicinePanelSettingRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/pharmacy/medicines');
    }

    public function rules(): array
    {
        return [
            'filter_is_free_distribution' => ['required', 'boolean'],
            'filter_is_controlled' => ['required', 'boolean'],
            'filter_is_judicial_order' => ['required', 'boolean'],
            'filter_is_high_cost' => ['required', 'boolean'],
            'filter_active' => ['required', 'boolean'],
            'filter_show_all' => ['required', 'boolean'],
        ];
    }
}
