<?php
require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\UserEquipeAps;

$u = User::where('active', 1)->first();
if ($u) {
    $u->update(['is_rt_psf' => true, 'rt_all_teams' => false]);
    UserEquipeAps::firstOrCreate(
        ['user_id' => $u->id, 'nu_ine' => '0001234567'],
        ['no_equipe' => 'ESF TESTE']
    );
    echo "RT configurado: user_id=" . $u->id . " email=" . $u->email . "\n";
} else {
    echo "Nenhum usuário ativo encontrado\n";
}

$count = UserEquipeAps::count();
echo "user_equipe_aps rows: " . $count . "\n";
$row = UserEquipeAps::first();
if ($row) {
    echo "First row: user_id=" . $row->user_id . " nu_ine=" . $row->nu_ine . " no_equipe=" . $row->no_equipe . "\n";
}
