<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetChatEnabledChatRealtimeConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['chat_enabled' => ['required', 'boolean']];
    }
}
