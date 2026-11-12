<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateChatRealtimeConfigRequest extends FormRequest
{
    /** Ao salvar, "active" é obrigatório; no teste de conexão é opcional. */
    protected bool $saving = true;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'engine' => ['required', Rule::in(['pusher', 'soketi'])],
            'active' => [$this->saving ? 'required' : 'nullable', 'boolean'],
            'app_id' => ['nullable', 'string', 'max:255'],
            'app_key' => ['nullable', 'string', 'max:255'],
            'app_secret' => ['nullable', 'string', 'max:500'],
            'cluster' => ['nullable', 'string', 'max:40', 'required_if:engine,pusher'],
            'host' => ['nullable', 'string', 'max:255', 'required_if:engine,soketi'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535', 'required_if:engine,soketi'],
            'scheme' => ['nullable', Rule::in(['http', 'https']), 'required_if:engine,soketi'],
            'use_tls' => ['nullable', 'boolean'],
            'rate_limit_decay_minutes' => ['nullable', 'integer', 'min:1', 'max:60'],
            'rate_limit_global' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'rate_limit_sync' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'rate_limit_messages' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'rate_limit_typing' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'rate_limit_presence' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'auto_open_on_message' => ['nullable', 'boolean'],
            'play_sound_on_message' => ['nullable', 'boolean'],
        ];
    }
}
