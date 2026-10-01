<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'sometimes|string|max:60|unique:access_profiles,nome,'.$this->route('access_profile'),
            'slug' => 'sometimes|string|max:60|unique:access_profiles,slug,'.$this->route('access_profile').'|alpha_dash',
            'descricao' => 'nullable|string|max:200',
            'ativo' => 'sometimes|boolean',
            'chat_enabled' => 'sometimes|boolean',
            'almoxarifado_create_enabled' => 'sometimes|boolean',
            'almoxarifado_approve_enabled' => 'sometimes|boolean',
            'almoxarifado_deliver_enabled' => 'sometimes|boolean',
            'client_trips_view_enabled' => 'sometimes|boolean',
            'client_report_view_enabled' => 'sometimes|boolean',
            'client_history_view_enabled' => 'sometimes|boolean',
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'integer|exists:system_pages,id',
        ];
    }
}
