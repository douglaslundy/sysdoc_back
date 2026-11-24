<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/** Textos e logo do portal público da ouvidoria (uma linha só, editada pelo admin). */
class OuvidoriaConfig extends Model
{
    protected $table = 'ouvidoria_config';

    protected $fillable = [
        'nome_marca', 'logo_path',
        'registrar_titulo', 'registrar_texto', 'registrar_itens',
        'consultar_titulo', 'consultar_texto', 'consultar_itens',
    ];

    protected $casts = ['registrar_itens' => 'array', 'consultar_itens' => 'array'];

    public const PADRAO = [
        'nome_marca' => 'DL Sistemas',
        'registrar_titulo' => 'Ouvidoria',
        'registrar_texto' => 'Registre reclamações, sugestões, elogios, denúncias e pedidos de informação.',
        'registrar_itens' => [
            'Você pode se identificar ou registrar de forma anônima.',
            'Acompanhe a resposta pelo protocolo e senha.',
            'A secretaria responde em até 30 dias.',
        ],
        'consultar_titulo' => 'Acompanhe sua manifestação',
        'consultar_texto' => 'Informe o protocolo e a senha que você recebeu ao registrar.',
        'consultar_itens' => ['Veja o andamento e a resposta oficial.'],
    ];

    public static function atual(): self
    {
        return self::query()->first() ?? self::create(self::PADRAO);
    }

    public function paraPortal(): array
    {
        return [
            'nome_marca' => $this->nome_marca,
            'logo_url' => $this->logo_path ? Storage::disk('public')->url($this->logo_path) : null,
            'registrar' => ['titulo' => $this->registrar_titulo, 'texto' => (string) $this->registrar_texto, 'itens' => array_values($this->registrar_itens ?? [])],
            'consultar' => ['titulo' => $this->consultar_titulo, 'texto' => (string) $this->consultar_texto, 'itens' => array_values($this->consultar_itens ?? [])],
        ];
    }
}
