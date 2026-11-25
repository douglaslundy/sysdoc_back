<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false;

    protected $fillable = ['profile', 'chat_access_override', 'name', 'preferred_name', 'phone', 'email', 'cpf', 'is_driver', 'is_rt_psf', 'rt_all_teams', 'password', 'active', 'inactive_date'];

    protected $hidden = ['password'];

    protected $casts = [
        'chat_access_override' => 'boolean',
    ];

    /** Compara só os dígitos: casa com o CPF salvo em qualquer formatação ('529.982.247-25', ' 52998224725 ', ...). */
    public function scopeWhereCpfIgual($query, string $cpf)
    {
        return $query->whereRaw(
            "REPLACE(REPLACE(REPLACE(REPLACE(cpf, '.', ''), '-', ''), ' ', ''), '/', '') = ?",
            [\App\Support\Cpf::digits($cpf)]
        );
    }

    public function specialities()
    {
        return $this->hasMany(Speciality::class, 'id_user');
    }

    public function queue()
    {
        return $this->hasMany(Queue::class, 'id_user');
    }

    public function ordinances()
    {
        return $this->hasMany(Ordinance::class, 'user_id');
    }

    public function equipeAps()
    {
        return $this->hasMany(UserEquipeAps::class, 'user_id');
    }

    public function secretarias(): BelongsToMany
    {
        return $this->belongsToMany(Secretaria::class, 'user_secretarias');
    }

    public function accessProfile(): HasOne
    {
        return $this->hasOne(AccessProfile::class, 'slug', 'profile');
    }

    /** Perfis de acesso ADICIONAIS (o principal fica em users.profile). */
    public function extraProfiles(): BelongsToMany
    {
        return $this->belongsToMany(AccessProfile::class, 'user_access_profiles')->withTimestamps();
    }

    /**
     * Perfis do usuário (principal + adicionais), lidos do banco a cada chamada (sem memória: uma mudança
     * no perfil vale na hora). Em listagens, carregue `extraProfiles` antes (with) para evitar N+1.
     */
    public function accessProfiles(): \Illuminate\Support\Collection
    {
        $principal = AccessProfile::where('slug', $this->profile)->first();

        return collect([$principal])->filter()->merge($this->extraProfilesCarregados())->unique('id')->values();
    }

    private function extraProfilesCarregados(): \Illuminate\Support\Collection
    {
        return $this->relationLoaded('extraProfiles') ? $this->extraProfiles : $this->extraProfiles()->get();
    }

    public function forgetProfileCache(): void
    {
        $this->unsetRelation('extraProfiles');
        $this->unsetRelation('accessProfile');
    }

    /** Slugs de todos os perfis (principal + adicionais), sem consultar o perfil principal. */
    public function profileSlugs(): array
    {
        return array_values(array_unique(array_filter(array_merge([$this->profile], $this->extraProfilesCarregados()->pluck('slug')->all()))));
    }

    /** Papel do usuário (gestor, tecnico...): perfil principal OU um dos adicionais. */
    public function hasProfile(string $slug): bool
    {
        return in_array($slug, $this->profileSlugs(), true);
    }

    /** Verdadeiro se QUALQUER perfil do usuário tem a permissão (coluna booleana do perfil). */
    public function anyProfileFlag(string $column): bool
    {
        return $this->accessProfiles()->contains(fn ($p) => (bool) $p->{$column});
    }

    /**
     * Unidades para onde o usuário pode enviar protocolo, já com as subunidades.
     * null = sem limite (admin, sem perfil, ou algum perfil sem destinos marcados).
     */
    public function protocolDestinoUnitIds(): ?array
    {
        $perfis = $this->accessProfiles();
        if ($this->profile === 'admin' || $perfis->isEmpty()) {
            return null;
        }

        $ids = [];
        foreach ($perfis as $perfil) {
            $marcadas = $perfil->protocolDestinoUnits()->pluck('protocol_organizational_units.id')->all();
            if ($marcadas === []) {
                return null;
            }
            foreach ($marcadas as $id) {
                array_push($ids, ...\App\Services\Protocol\UnitTree::withDescendantIds((int) $id, true));
            }
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    /** Usuários com o perfil principal OU adicional informado. */
    public function scopeWithProfile($query, string $slug)
    {
        return $query->where(fn ($q) => $q->where('users.profile', $slug)->orWhereIn('users.id', self::idsComPerfilAdicional($slug)));
    }

    /** Subconsulta com os ids de quem tem o perfil como ADICIONAL (para scopes e Rule::exists). */
    public static function idsComPerfilAdicional(string $slug): \Illuminate\Database\Query\Builder
    {
        return \Illuminate\Support\Facades\DB::table('user_access_profiles as uap')
            ->join('access_profiles as ap', 'ap.id', '=', 'uap.access_profile_id')
            ->where('ap.slug', $slug)
            ->select('uap.user_id');
    }

    public function protocolUnits(): HasMany
    {
        return $this->hasMany(ProtocolUserUnit::class, 'user_id');
    }

    public function canUseChat(): bool
    {
        if (! \App\Models\ChatRealtimeConfig::chatGloballyEnabled()) {
            return false;
        }

        if ($this->profile === 'admin') {
            return true;
        }

        if ($this->chat_access_override !== null) {
            return (bool) $this->chat_access_override;
        }

        return $this->anyProfileFlag('chat_enabled');
    }

    /**
     * Mesma regra de canUseChat(), mas usando um mapa perfil => chat_enabled ja
     * carregado (AccessProfile::pluck('chat_enabled', 'slug')). Use em listagens:
     * canUseChat() faz uma consulta por usuario (N+1).
     */
    public function canUseChatWith($chatEnabledByProfile): bool
    {
        if (! \App\Models\ChatRealtimeConfig::chatGloballyEnabled()) {
            return false;
        }

        if ($this->profile === 'admin') {
            return true;
        }

        if ($this->chat_access_override !== null) {
            return (bool) $this->chat_access_override;
        }

        foreach ($this->profileSlugs() as $slug) {
            if ($chatEnabledByProfile[$slug] ?? false) {
                return true;
            }
        }

        return false;
    }

    public function canUseAlmoxarifadoAction(string $action): bool
    {
        if ($this->profile === 'admin') {
            return true;
        }

        $column = match ($action) {
            'create' => 'almoxarifado_create_enabled',
            'approve' => 'almoxarifado_approve_enabled',
            'deliver' => 'almoxarifado_deliver_enabled',
            default => null,
        };

        return $column ? $this->anyProfileFlag($column) : false;
    }

    public function canViewClientTrips(): bool
    {
        if ($this->profile === 'admin') {
            return true;
        }

        return $this->anyProfileFlag('client_trips_view_enabled');
    }

    public function canViewClientReport(): bool
    {
        if ($this->profile === 'admin') {
            return true;
        }

        return $this->anyProfileFlag('client_report_view_enabled');
    }

    /** Abrir OS para qualquer secretaria e encaminhar OS a outra secretaria/técnico: permissão do perfil; admin sempre. */
    public function canEncaminharOs(): bool
    {
        if ($this->profile === 'admin') {
            return true;
        }

        return $this->anyProfileFlag('os_encaminhar_enabled');
    }

    /** Ver o historico (auditoria) de um cidadao: permissao propria por perfil; admin sempre. */
    public function canViewClientHistory(): bool
    {
        if ($this->profile === 'admin') {
            return true;
        }

        return $this->anyProfileFlag('client_history_view_enabled');
    }

    /**
     * O que este usuário pode cadastrar em protocolo, definido no perfil de acesso.
     * types/statuses null = sem restrição; visibilities nunca vazio (padrão: só privado). Admin não tem restrição.
     */
    public function protocolCreationRules(): array
    {
        if ($this->profile === 'admin') {
            return ['types' => null, 'statuses' => null, 'visibilities' => ['private', 'public'], 'can_change_visibility' => true];
        }

        $perfis = $this->accessProfiles();
        $uniao = function (string $campo) use ($perfis) {
            // null = sem restrição: basta um perfil sem restrição para liberar tudo.
            $valores = $perfis->map(fn ($p) => $p->{$campo});
            if ($valores->isEmpty() || $valores->contains(null)) {
                return null;
            }

            return $valores->flatten()->unique()->values()->all();
        };
        $visibilidades = array_values(array_intersect(['private', 'public'], $perfis->isEmpty()
            ? ['private']
            : $perfis->flatMap(fn ($p) => (array) ($p->protocol_allowed_visibilities ?? ['private']))->unique()->all()));

        return [
            'types' => $uniao('protocol_allowed_types'),
            'statuses' => $uniao('protocol_allowed_statuses'),
            'visibilities' => $visibilidades ?: ['private'],
            'can_change_visibility' => $this->anyProfileFlag('protocol_can_change_visibility'),
        ];
    }

    public function chatDisplayName(): string
    {
        $preferred = trim((string) ($this->preferred_name ?? ''));

        return $preferred !== '' ? $preferred : (string) $this->name;
    }

    public function whatsappPhoneNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($this->phone ?? ''));

        if (strlen((string) $digits) === 10 || strlen((string) $digits) === 11) {
            return '55' . $digits;
        }

        return strlen((string) $digits) >= 12 ? $digits : null;
    }
}
