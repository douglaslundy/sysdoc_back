<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoomRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|max:100',
            'description' => 'nullable|max:50',
            'status' => 'required|in:OPEN,BUSY,CLOSED',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'O campo nome é obrigatório.',
            'name.max' => 'O campo nome não pode ter mais de :max caracteres.',
            'description.max' => 'O campo descrição não pode ter mais de :max caracteres.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O valor do campo status deve ser um dos seguintes valores: :values.',
        ];
    }
}
