<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;

class StoreQueueAttachmentRequest extends FormRequest
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
            'file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240', 'required_without:files'],
            'files' => ['nullable', 'array', 'required_without:file'],
            'files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
