<?php

namespace App\Http\Requests;

use App\Models\Queue;
use App\Services\Authorization\PagePermissionService;
use App\Services\Authorization\SpecialityPermissionService;

class UpdateQueueRequest extends BaseApiFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        if ($user === null || ! app(PagePermissionService::class)->canAccess($user, '/queue')) {
            return false;
        }

        $queue = Queue::find($this->route('queue'));

        if ($queue === null) {
            return true;
        }

        $service = app(SpecialityPermissionService::class);

        if (! $service->canEdit($user, $queue->id_specialities)) {
            return false;
        }

        $newSpecialityId = (int) $this->input('id_specialities', $queue->id_specialities);

        if ($newSpecialityId > 0 && $newSpecialityId !== (int) $queue->id_specialities) {
            return $service->canInsert($user, $newSpecialityId);
        }

        return true;
    }

    public function rules(): array
    {
        // 404 se o item for de outra secretaria; a especialidade nova tem de ser da mesma secretaria do item.
        $sid = \App\Support\EscopoAlmoxarifado::alvo(Queue::class, $this->route('queue'));

        return [
            'id_client' => ['sometimes', 'required', 'exists:clients,id'],
            'id_specialities' => ['sometimes', 'required', 'integer', \App\Support\EscopoAlmoxarifado::existe('specialities', $sid)],
            'id_user' => ['sometimes', 'required', 'exists:users,id'],
            'done' => ['boolean'],
            'date_of_realized' => ['nullable', 'date'],
            'urgency' => ['sometimes', 'required', 'boolean'],
            'obs' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
