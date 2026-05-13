<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class MedicineComplianceIndexRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccessAny($user, [
                '/pharmacy/daily-status',
                '/pharmacy/monthly-acquisitions',
                '/pharmacy/compliance',
            ]);
    }

    public function rules(): array
    {
        return [];
    }
}
