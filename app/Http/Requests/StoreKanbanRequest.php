<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\LimpaTextoRico;
use App\Models\Secretaria;
use App\Rules\TextoRico;
use Illuminate\Foundation\Http\FormRequest;

class StoreKanbanRequest extends FormRequest
{
    use LimpaTextoRico;

    protected function prepareForValidation(): void
    {
        $this->limparTextoRico(['descricao']);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => 'required|string|max:200',
            'descricao' => ['nullable', 'string', new TextoRico(16000)],
            'status' => 'nullable|string|max:40',
            'prioridade' => 'nullable|string|max:20',
            'vencimento' => 'nullable|date',
            'secretaria_id' => Secretaria::regraId(),
            'responsavel_id' => 'nullable|integer|exists:users,id',
            'visibility' => 'nullable|string|in:public,private',
            'ordem' => 'nullable|integer|min:0',
        ];
    }
}
