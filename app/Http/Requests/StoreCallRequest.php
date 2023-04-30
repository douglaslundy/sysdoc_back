<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCallRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules()
    {
        return [
            'call_datetime' => 'required|date',
            'user_id' => 'required|exists:users,id',
            'client_id' => 'required|exists:clients,id',
            'status' => 'required|in:OPEN,IN_PROGRESS,CLOSED,ABANDONED',
        ];
    }

    public function messages(): array
    {
        return [
            'call_datetime.required' => 'A data e hora da chamada são obrigatórios',
            'call_datetime.date' => 'A data e hora da chamada devem ser uma data válida',
            'user_id.required' => 'O ID do usuário é obrigatório',
            'user_id.exists' => 'O ID do usuário não é válido',
            'client_id.required' => 'O ID do cliente é obrigatório',
            'client_id.exists' => 'O ID do cliente não é válido',
            'status.required' => 'O status da chamada é obrigatório',
            'status.in' => 'O status da chamada deve ser um dos seguintes valores: OPEN, IN_PROGRESS, CLOSED ou ABANDONED',
        ];
    }
}
