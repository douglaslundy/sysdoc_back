<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    public $timestamps = false;

    protected $fillable = ['profile', 'chat_access_override', 'name', 'preferred_name', 'phone', 'email', 'cpf', 'is_driver', 'is_rt_psf', 'rt_all_teams', 'password', 'active', 'inactive_date'];

    protected $hidden = ['password'];

    protected $casts = [
        'chat_access_override' => 'boolean',
    ];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
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

    public function accessProfile(): HasOne
    {
        return $this->hasOne(AccessProfile::class, 'slug', 'profile');
    }

    public function protocolUnits(): HasMany
    {
        return $this->hasMany(ProtocolUserUnit::class, 'user_id');
    }

    public function canUseChat(): bool
    {
        if ($this->profile === 'admin') {
            return true;
        }

        if ($this->chat_access_override !== null) {
            return (bool) $this->chat_access_override;
        }

        return (bool) $this->accessProfile()->value('chat_enabled');
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

        return $column ? (bool) $this->accessProfile()->value($column) : false;
    }

    public function chatDisplayName(): string
    {
        $preferred = trim((string) ($this->preferred_name ?? ''));

        return $preferred !== '' ? $preferred : (string) $this->name;
    }

    public function whatsappPhoneNumber(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) ($this->phone ?? ''));

        return strlen((string) $digits) >= 10 ? $digits : null;
    }
}
