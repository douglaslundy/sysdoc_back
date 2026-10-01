<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccessProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:60|unique:access_profiles,nome',
            'slug' => 'required|string|max:60|unique:access_profiles,slug|alpha_dash',
            'descricao' => 'nullable|string|max:200',
            'chat_enabled' => 'nullable|boolean',
            'almoxarifado_create_enabled' => 'nullable|boolean',
            'almoxarifado_approve_enabled' => 'nullable|boolean',
            'almoxarifado_deliver_enabled' => 'nullable|boolean',
            'client_trips_view_enabled' => 'nullable|boolean',
            'client_report_view_enabled' => 'nullable|boolean',
            'client_history_view_enabled' => 'nullable|boolean',
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'integer|exists:system_pages,id',
        ];
    }
}
