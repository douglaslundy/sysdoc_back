<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLocationLogQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'uuid' => 'required|uuid',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ];
    }
}
