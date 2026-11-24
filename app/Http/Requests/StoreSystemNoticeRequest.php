<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\LimpaTextoRico;
use App\Rules\TextoRico;
use Illuminate\Foundation\Http\FormRequest;

class StoreSystemNoticeRequest extends FormRequest
{
    use LimpaTextoRico;

    protected function prepareForValidation(): void
    {
        $this->limparTextoRico(['body']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:180'],
            'body' => ['required', 'string', new TextoRico(16000, 1)],
            'image_data' => ['nullable', 'string'],
            'times_per_day' => ['required', 'integer', 'min:1', 'max:24'],
            'interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'target_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'valid_until' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
