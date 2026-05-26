<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;

class DownloadCurrentMedicineStockRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccessAny($user, ['/pharmacy/daily-status', '/pharmacy/medicines']);
    }

    public function rules(): array
    {
        return [];
    }
}
