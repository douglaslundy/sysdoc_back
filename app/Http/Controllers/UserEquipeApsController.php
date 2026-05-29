<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserEquipeAps;
use Illuminate\Http\Request;

class UserEquipeApsController extends Controller
{
    /**
     * GET /users/{user}/equipe-aps
     * Retorna a config RT + equipes de um usuário específico (admin).
     */
    public function show(User $user)
    {
        return response()->json([
            'is_rt_psf'    => (bool) $user->is_rt_psf,
            'rt_all_teams' => (bool) $user->rt_all_teams,
            'equipes'      => $user->equipeAps->map(fn($e) => [
                'nu_ine'    => $e->nu_ine,
                'no_equipe' => $e->no_equipe,
            ])->values(),
        ]);
    }

    /**
     * PUT /users/{user}/equipe-aps
     * Salva is_rt_psf, rt_all_teams e sincroniza equipes (admin).
     */
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'is_rt_psf'           => 'required|boolean',
            'rt_all_teams'        => 'required|boolean',
            'equipes'             => 'nullable|array',
            'equipes.*.nu_ine'    => 'required_with:equipes|string|max:10',
            'equipes.*.no_equipe' => 'required_with:equipes|string|max:100',
        ]);

        $user->update([
            'is_rt_psf'    => $data['is_rt_psf'],
            'rt_all_teams' => $data['rt_all_teams'],
        ]);

        // Sincronizar equipes: deletar as removidas, inserir as novas.
        UserEquipeAps::where('user_id', $user->id)->delete();

        if (!empty($data['equipes']) && $data['is_rt_psf'] && !$data['rt_all_teams']) {
            foreach ($data['equipes'] as $eq) {
                UserEquipeAps::create([
                    'user_id'   => $user->id,
                    'nu_ine'    => $eq['nu_ine'],
                    'no_equipe' => $eq['no_equipe'],
                ]);
            }
        }

        return response()->json([
            'is_rt_psf'    => (bool) $user->fresh()->is_rt_psf,
            'rt_all_teams' => (bool) $user->fresh()->rt_all_teams,
            'equipes'      => $user->fresh()->equipeAps->map(fn($e) => [
                'nu_ine'    => $e->nu_ine,
                'no_equipe' => $e->no_equipe,
            ])->values(),
        ]);
    }
}
