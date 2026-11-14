<?php

namespace App\Http\Requests;

use App\Models\ChatConversation;
use App\Services\Secretarias\SecretariaMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/** Base das requisições de conversa do chat: só participante que ainda atende a secretaria da conversa (403 antes da validação). */
abstract class ChatParticipantRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var ChatConversation $conversation */
        $conversation = $this->route('conversation');
        $userId = (int) $this->user()->id;

        $allowed = DB::table('chat_conversation_participants')
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->exists();

        // Quem saiu da secretaria da conversa deixa de acessá-la.
        return $allowed && SecretariaMembership::atende($userId, (int) $conversation->secretaria_id);
    }

    protected function failedAuthorization(): void
    {
        throw new HttpException(403, 'Conversa não autorizada.');
    }
}
