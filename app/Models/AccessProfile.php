<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessProfile extends Model
{
    protected $fillable = [
        'nome',
        'slug',
        'descricao',
        'ativo',
        'chat_enabled',
        'almoxarifado_create_enabled',
        'almoxarifado_approve_enabled',
        'almoxarifado_deliver_enabled',
        'client_trips_view_enabled',
        'client_report_view_enabled',
        'client_history_view_enabled',
        'protocol_allowed_types',
        'protocol_allowed_statuses',
        'protocol_allowed_visibilities',
        'protocol_can_change_visibility',
    ];

    protected $casts = [
        'ativo' => 'boolean',
        'chat_enabled' => 'boolean',
        'almoxarifado_create_enabled' => 'boolean',
        'almoxarifado_approve_enabled' => 'boolean',
        'almoxarifado_deliver_enabled' => 'boolean',
        'client_trips_view_enabled' => 'boolean',
        'client_report_view_enabled' => 'boolean',
        'client_history_view_enabled' => 'boolean',
        'protocol_allowed_types' => 'array',
        'protocol_allowed_statuses' => 'array',
        'protocol_allowed_visibilities' => 'array',
        'protocol_can_change_visibility' => 'boolean',
    ];

    public function pages()
    {
        return $this->belongsToMany(SystemPage::class, 'profile_page_permissions');
    }
}
