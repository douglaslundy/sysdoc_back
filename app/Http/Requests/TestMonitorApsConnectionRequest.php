<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TestMonitorApsConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'host' => 'required|string',
            'database' => 'required|string',
            'user' => 'required|string',
            'port' => 'nullable|integer',
            'password' => 'nullable|string',
        ];
    }
}
