<?php

namespace App\Http\Requests;

class TypingChatRequest extends ChatParticipantRequest
{
    public function rules(): array
    {
        return ['typing' => ['required', 'boolean']];
    }
}
