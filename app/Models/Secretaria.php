<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Secretaria extends Model
{
    public const SAUDE_SLUG = 'saude';

    // Páginas de gestão (admin/gestor): não dependem da liberação da secretaria.
    public const PAGINAS_GESTAO = ['/users', '/perfis', '/secretarias', '/usuarios/aprovacoes'];

    protected $fillable = ['nome', 'slug', 'ativa'];

    protected $casts = ['ativa' => 'boolean'];

    // REGRA: toda migration/seeder que cria uma system_page deve chamar liberarPaginas(), senão ninguém a vê.
    public static function liberarPaginas(array $paths, ?array $slugs = null): void
    {
        // Migrations antigas chamam os seeders antes da tabela existir; a migration de backfill cobre esse caso.
        if (! \Illuminate\Support\Facades\Schema::hasTable('secretaria_pages')) {
            return;
        }

        $ids = SystemPage::whereIn('path', $paths)->pluck('id')->all();

        static::whereIn('slug', $slugs ?? [self::SAUDE_SLUG])->get()
            ->each(fn (self $s) => $s->pages()->syncWithoutDetaching($ids));
    }

    /** Id da Saúde (cria se faltar): destino explícito de seeders e dados antigos. */
    public static function saudeId(): int
    {
        return static::firstOrCreate(['slug' => self::SAUDE_SLUG], ['nome' => 'Saúde', 'ativa' => true])->id;
    }

    /** Regra de validação do campo secretaria_id (admin em "Todas" escolhe; os demais têm o valor ignorado). */
    public static function regraId(): array
    {
        return ['nullable', 'integer', \Illuminate\Validation\Rule::exists('secretarias', 'id')->where('ativa', true)];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_secretarias');
    }

    public function pages(): BelongsToMany
    {
        return $this->belongsToMany(SystemPage::class, 'secretaria_pages');
    }
}
