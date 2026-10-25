<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ListIluminacaoPostesRequest extends FormRequest
{
    public const MAX_DIAS = 366;

    public function authorize(): bool
    {
        // O acesso é decidido por config/route_permissions.php (middleware route.pages).
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['nullable', 'in:pendentes,concluidos,todos'],
            'de' => ['nullable', 'date_format:Y-m-d'],
            'ate' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:de'],
            'busca' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            if ($v->errors()->isNotEmpty() || ! $this->filled('de') || ! $this->filled('ate')) {
                return;
            }

            if (Carbon::parse($this->input('de'))->diffInDays(Carbon::parse($this->input('ate'))) > self::MAX_DIAS) {
                $v->errors()->add('ate', 'O período máximo é de '.self::MAX_DIAS.' dias.');
            }
        });
    }
}
