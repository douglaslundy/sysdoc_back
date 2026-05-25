<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEstabelecimentoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('cnaes')) {
            $this->merge(['cnaes' => $this->normalizeCnaes($this->cnaes)]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_responsavel' => ['sometimes', 'required', 'string', 'max:255'],
            'nome_estabelecimento' => ['sometimes', 'required', 'string', 'max:255'],
            'razao_social' => ['nullable', 'string', 'max:255'],
            'nome_fantasia' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['nullable', 'string', 'max:18', 'regex:/^\d{2}\.\d{3}\.\d{3}\/\d{4}-\d{2}$/'],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['sometimes', 'required', 'string', 'max:500'],
            'cnaes' => ['sometimes', 'required', 'array', 'min:1'],
            'cnaes.*' => ['required', 'string', 'regex:/^\d{4}-\d\/\d{2}$/'],
            'obs' => ['nullable', 'string', 'max:5000'],
        ];
    }

    private function normalizeCnaes(mixed $raw): array
    {
        $values = is_array($raw) ? $raw : [$raw];
        $out = [];

        foreach ($values as $item) {
            if ($item === null) {
                continue;
            }
            $text = (string) $item;
            preg_match_all('/\d{2}\.?\d{2}-?\d\/?-?\d{2}|\d{4}-\d\/\d{2}/', $text, $matches);
            foreach (($matches[0] ?? []) as $found) {
                $digits = preg_replace('/\D/', '', $found);
                if (strlen($digits) !== 7) {
                    continue;
                }
                $out[] = substr($digits, 0, 4).'-'.substr($digits, 4, 1).'/'.substr($digits, 5, 2);
            }
        }

        return array_values(array_unique($out));
    }
}

