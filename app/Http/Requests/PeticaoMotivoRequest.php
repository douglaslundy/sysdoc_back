<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Support\EscopoAlmoxarifado;

class PeticaoMotivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        // O acesso por página (/peticao-motivos) é aplicado por config/route_permissions.php.
        return true;
    }

    public function rules(): array
    {
        $ignore = $this->route('motivo')?->id ?? $this->route('motivo');

        // PUT: a secretaria e a do motivo; POST: a ativa, ou a informada pelo admin em "Todas".
        $sid = EscopoAlmoxarifado::alvo(\App\Models\PeticaoMotivo::class, $ignore, $this->input('secretaria_id'));

        return [
            ...($ignore === null ? ['secretaria_id' => EscopoAlmoxarifado::regraCampo()] : []),
            'nome' => ['required', 'string', 'max:120', EscopoAlmoxarifado::unico('peticao_motivos', 'nome', $sid)->ignore($ignore)],
            'descricao' => ['nullable', 'string', 'max:255'],
            'unit_id' => ['nullable', 'integer', EscopoAlmoxarifado::existe('protocol_organizational_units', $sid)],
            'ativo' => ['sometimes', 'boolean'],
            'ordem' => ['sometimes', 'integer', 'min:0', 'max:65535'],
        ];
    }
}
