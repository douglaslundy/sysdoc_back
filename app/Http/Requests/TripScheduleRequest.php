<?php

namespace App\Http\Requests;

use App\Services\Authorization\PagePermissionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Base da agenda de viagens: a permissão de página é checada antes da validação (403 antes de 422). */
abstract class TripScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return (bool) ($user && app(PagePermissionService::class)->canAccessAny($user, ['/trips', '/trips/agenda']));
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(response()->json(['message' => 'Você não possui permissão para executar esta ação.'], 403));
    }
}
