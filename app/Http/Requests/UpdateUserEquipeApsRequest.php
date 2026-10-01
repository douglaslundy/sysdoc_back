<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserEquipeApsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_rt_psf'           => 'required|boolean',
            'rt_all_teams'        => 'required|boolean',
            'equipes'             => 'nullable|array',
            'equipes.*.nu_ine'    => 'required_with:equipes|string|max:10',
            'equipes.*.no_equipe' => 'required_with:equipes|string|max:100',
        ];
    }
}
