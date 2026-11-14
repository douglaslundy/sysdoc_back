<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ChecksGestorProfileScope;
use Illuminate\Foundation\Http\FormRequest;

class StoreAccessProfileRequest extends FormRequest
{
    use ChecksGestorProfileScope;

    public function withValidator($validator): void
    {
        $validator->after(fn ($v) => $this->checkGestorScope($v));
    }

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
            'protocol_allowed_types' => 'nullable|array',
            'protocol_allowed_types.*' => 'string|exists:protocol_types,codigo',
            'protocol_allowed_statuses' => 'nullable|array',
            'protocol_allowed_statuses.*' => ['string', \Illuminate\Validation\Rule::in(\App\Http\Requests\StoreProtocolRequest::STATUSES_INICIAIS)],
            'protocol_allowed_visibilities' => 'nullable|array',
            'protocol_allowed_visibilities.*' => 'string|in:public,private',
            'protocol_can_change_visibility' => 'nullable|boolean',
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'integer|exists:system_pages,id',
        ];
    }
}
