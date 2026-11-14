<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ChecksGestorProfileScope;
use App\Models\AccessProfile;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAccessProfileRequest extends FormRequest
{
    use ChecksGestorProfileScope;

    public function withValidator($validator): void
    {
        $validator->after(fn ($v) => $this->checkGestorScope($v));
    }

    /** Não-admin edita só perfil que ele criou (perfil inexistente cai no 404 do controller). */
    public function authorize(): bool
    {
        $user = $this->user();
        if ($user?->profile === 'admin') {
            return true;
        }
        $perfil = AccessProfile::find($this->route('access_profile'));

        return ! $perfil || ($user && (int) $perfil->created_by_id === (int) $user->id);
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
            'protocol_allowed_types' => 'nullable|array',
            'protocol_allowed_types.*' => 'string|exists:protocol_types,codigo',
            'protocol_allowed_statuses' => 'nullable|array',
            'protocol_allowed_statuses.*' => ['string', \Illuminate\Validation\Rule::in(\App\Http\Requests\StoreProtocolRequest::STATUSES_INICIAIS)],
            'protocol_allowed_visibilities' => 'nullable|array',
            'protocol_allowed_visibilities.*' => 'string|in:public,private',
            'protocol_can_change_visibility' => 'sometimes|boolean',
            'page_ids' => 'nullable|array',
            'page_ids.*' => 'integer|exists:system_pages,id',
        ];
    }
}
