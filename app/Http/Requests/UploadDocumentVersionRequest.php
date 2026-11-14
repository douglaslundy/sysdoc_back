<?php

namespace App\Http\Requests;

class UploadDocumentVersionRequest extends DocumentWriteRequest
{
    public function authorize(): bool
    {
        $this->ensureCanManageRecord();

        return true;
    }

    public function rules(): array
    {
        return [
            'arquivo' => ['required', 'file', 'max:51200'],
        ];
    }
}
