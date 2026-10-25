<?php

namespace App\Http\Requests;

use App\Models\PeticaoMotivo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Petição pública (denúncia, vistoria...): qualquer pessoa pode enviar; identificação é opcional. */
class StoreDenunciaRequest extends FormRequest
{
    public const MAX_FILES = 5;

    public const MAX_FILE_KB = 10240;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // O motivo é obrigatório assim que existir algum motivo ativo cadastrado; antes disso
        // (transição, até o admin cadastrar) a petição continua sendo aceita sem motivo.
        $haMotivos = PeticaoMotivo::where('ativo', true)->exists();

        return [
            'motivo_id' => [
                $haMotivos ? 'required' : 'nullable',
                'integer',
                Rule::exists('peticao_motivos', 'id')->where('ativo', true),
            ],
            'assunto' => ['required', 'string', 'max:200'],
            'descricao_denuncia' => ['required', 'string', 'max:4000'],
            'local_endereco' => ['required', 'string', 'max:255'],
            'estabelecimento_nome_informado' => ['nullable', 'string', 'max:200'],
            'denunciante_nome' => ['nullable', 'string', 'max:150'],
            'denunciante_contato' => ['nullable', 'string', 'max:150'],
            // Campo isca: pessoas não veem, robôs preenchem.
            'website' => ['nullable', 'string'],
            'files' => ['nullable', 'array', 'max:'.self::MAX_FILES],
            'files.*' => [
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'mimetypes:image/jpeg,image/png,image/webp,application/pdf',
                'max:'.self::MAX_FILE_KB,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'files.max' => 'Envie no máximo '.self::MAX_FILES.' arquivos.',
            'files.*.mimes' => 'Envie apenas fotos (JPG, PNG, WEBP) ou PDF.',
            'files.*.mimetypes' => 'Envie apenas fotos (JPG, PNG, WEBP) ou PDF.',
            'files.*.max' => 'Cada arquivo pode ter no máximo 10 MB.',
        ];
    }
}
