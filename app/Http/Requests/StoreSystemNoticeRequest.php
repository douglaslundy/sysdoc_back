<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSystemNoticeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string'],
            'image_data' => ['nullable', 'string'],
            'times_per_day' => ['required', 'integer', 'min:1', 'max:24'],
            'interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
