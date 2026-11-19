<?php

namespace App\Http\Requests;

use App\Models\OrdemServico;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrdemServicoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $secretaria = (int) $this->input('secretaria_id');
        $user = $this->user();

        return [
            // Quem tem a permissão de encaminhar (admin sempre) abre para qualquer secretaria; os demais só para as suas.
            'secretaria_id' => array_filter(['required', 'integer', Rule::exists('secretarias', 'id')->where('ativa', true),
                $user?->canEncaminharOs() ? null : Rule::in($user?->secretarias()->pluck('secretarias.id')->all() ?? [])]),
            'tipo_servico_id' => ['required', 'integer', Rule::exists('os_tipos_servico', 'id')->where('secretaria_id', $secretaria)->where('ativo', true)],
            'tecnico_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($q) => $q->where('active', true)->where(fn ($p) => $p->where('profile', 'tecnico')->orWhereIn('id', \App\Models\User::idsComPerfilAdicional('tecnico'))))],
            'local' => ['required', 'string', 'max:160'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:5000'],
            'problema' => ['nullable', 'string', 'max:5000'],
            'prioridade' => ['nullable', Rule::in(OrdemServico::PRIORIDADES)],
            'prazo' => ['nullable', 'date', 'after_or_equal:today'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'contato_nome' => ['nullable', 'string', 'max:120'],
            'contato_telefone' => ['nullable', 'string', 'max:30'],
            'fotos' => ['nullable', 'array', 'max:6'],
            'fotos.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }
}
