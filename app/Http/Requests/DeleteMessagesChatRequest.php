<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMessagesChatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message_ids' => ['required', 'array', 'min:1', 'max:100'],
            'message_ids.*' => ['required', 'integer', 'distinct', \Illuminate\Validation\Rule::exists('chat_messages', 'id')->where(function ($q) {
                // Mensagens só de conversas da secretaria ativa (exists cru ignora o escopo global).
                $ativa = app(\App\Services\Secretarias\ActiveSecretaria::class);
                if ($ativa->inRequest() && ! $ativa->isAll()) {
                    $q->whereIn('conversation_id', \Illuminate\Support\Facades\DB::table('chat_conversations')->select('id')->where('secretaria_id', $ativa->id() ?? 0));
                }
            })],
        ];
    }
}
