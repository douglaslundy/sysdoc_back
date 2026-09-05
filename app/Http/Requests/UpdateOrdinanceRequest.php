<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrdinanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && app(PagePermissionService::class)->canAccess($user, '/ordinance');
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(['normativa', 'ordinatoria'])],
            'title' => ['required', 'string', 'max:255'],
            'subject' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'legal_basis' => ['nullable', 'string'],
            'signatory_name' => ['required', 'string', 'max:150'],
            'signatory_role' => ['nullable', 'string', 'max:150'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:30720'],
            'file_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
