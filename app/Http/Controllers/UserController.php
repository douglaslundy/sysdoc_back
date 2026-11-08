<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Models\UserPresence;
use App\Services\Secretarias\GestorAlert;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        // Gestão de usuários: admin ou gestor (o gestor fica restrito às suas secretarias, ver scopedTo).
        $this->middleware(function (Request $request, $next) {
            if (! in_array($request->user()?->profile, ['admin', 'gestor'], true)) {
                return response()->json(['message' => 'Acesso restrito a administradores e gestores.'], 403);
            }

            return $next($request);
        })->except('presence');
    }

    /** Admin vê todos; gestor só quem divide secretaria com ele, nunca administradores. */
    private function scopedTo(User $actor)
    {
        $query = User::query();

        if ($actor->profile === 'admin') {
            return $query;
        }

        return $query->where('profile', '!=', 'admin')
            ->whereHas('secretarias', fn ($q) => $q->whereIn('secretarias.id', $this->actorSecretariaIds($actor)));
    }

    private function actorSecretariaIds(User $actor): array
    {
        return $actor->secretarias()->pluck('secretarias.id')->all();
    }

    /** Só id e nome; gestor vê apenas as secretarias que também são dele. Exige `secretarias` carregada. */
    private function secretariasPayload(User $actor, User $user, ?array $actorIds): array
    {
        return $user->secretarias
            ->when($actor->profile !== 'admin', fn ($c) => $c->whereIn('id', $actorIds))
            ->map(fn ($s) => ['id' => $s->id, 'nome' => $s->nome])
            ->values()
            ->all();
    }

    private function authorizeTarget(Request $request, User $user): void
    {
        abort_unless($this->scopedTo($request->user())->whereKey($user->id)->exists(), 403, 'Usuário fora das suas secretarias.');
    }

    /** Gestor mexe só nos vínculos das próprias secretarias: os demais vínculos do alvo ficam intactos. */
    private function syncSecretarias(User $actor, User $user, array $ids): void
    {
        if ($actor->profile === 'admin') {
            $user->secretarias()->sync($ids);
        } else {
            $user->secretarias()->detach($this->actorSecretariaIds($actor));
            $user->secretarias()->syncWithoutDetaching($ids);
        }

        // Perdeu o vínculo com uma secretaria: as lotações nas unidades dela deixam de valer.
        \App\Support\LotacaoPorSecretaria::inativarForaDosVinculos($user);
    }

    private function alertIfGestorAssigned(Request $request, User $user, ?string $previousProfile): void
    {
        if ($request->user()->profile === 'gestor' && $user->profile === 'gestor' && $previousProfile !== 'gestor') {
            GestorAlert::perfilGestorAtribuido($request->user(), $user);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $actor = $request->user();
        $actorIds = $actor->profile === 'admin' ? null : $this->actorSecretariaIds($actor);
        $users = $this->scopedTo($actor)->with(['protocolUnits.unit:id,parent_id,tipo,codigo,nome,ativo', 'secretarias:secretarias.id,secretarias.nome'])
            ->where('active', true)
            ->orderBy('id', 'desc')
            ->get();
        $presences = UserPresence::query()->get()->keyBy('user_id');
        // Um mapa perfil => chat_enabled em vez de uma consulta por usuario (canUseChat()).
        $chatEnabledByProfile = \App\Models\AccessProfile::query()->pluck('chat_enabled', 'slug');

        return $users->map(function (User $user) use ($presences, $chatEnabledByProfile, $actor, $actorIds) {
            $presence = $presences->get($user->id);
            $lastSeenAt = $presence?->last_seen_at;
            $isOnline = $lastSeenAt !== null && $lastSeenAt->greaterThanOrEqualTo(now()->subMinutes(5));

            return [
                ...$user->toArray(),
                'is_online' => $isOnline,
                'last_seen_at' => $lastSeenAt?->toDateTimeString(),
                'last_path' => $presence?->last_path,
                'can_chat' => $user->canUseChatWith($chatEnabledByProfile),
                'secretarias' => $this->secretariasPayload($actor, $user, $actorIds),
                'protocol_unit_ids' => $user->protocolUnits
                    ->where('ativo', true)
                    ->pluck('protocol_organizational_unit_id')
                    ->values(),
            ];
        });
    }

    public function presence(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Usuário não autenticado.'], 401);
        }

        UserPresence::updateOrCreate(
            ['user_id' => $user->id],
            [
                'last_seen_at' => now(),
                'last_path' => $request->input('path'),
            ]
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(UserRequest $request)
    {

        $array = ['status' => 'created'];
        // Gestor só preenche dados cadastrais e perfil: flags de chat/RT/inativação são do admin.
        $data = $request->user()->profile === 'admin'
            ? $request->except(['password2', 'protocol_unit_ids', 'secretaria_ids'])
            : $request->only(['profile', 'name', 'preferred_name', 'phone', 'email', 'cpf', 'password', 'is_driver']);
        $data['password'] = Hash::make($data['password']);
        $existing = $request->reactivatableUser();
        if ($existing) {
            // CPF já cadastrado e inativado: reativa o registro antigo com os dados novos.
            $data['active'] = true;
            $data['inactive_date'] = null;
            $array['status'] = 'reactivated';
        }
        $user = DB::transaction(function () use ($data, $request, $existing) {
            if ($existing) {
                $existing->update($data);
                // Reativação não herda lotações antigas (de qualquer secretaria, sem o escopo do aprovador).
                \App\Models\ProtocolUserUnit::withoutGlobalScopes()->where('user_id', $existing->id)->update(['ativo' => false]);
                $user = $existing;
            } else {
                $user = User::create($data);
            }
            if ($request->user()->profile === 'admin') {
                $this->syncProtocolUnits($user, $request->input('protocol_unit_ids', []));
            }
            // Gestor só troca os vínculos das suas secretarias; os de fora ficam (reativação).
            $this->syncSecretarias($request->user(), $user, $request->input('secretaria_ids', []));

            return $user;
        });
        $this->alertIfGestorAssigned($request, $user, null);
        $array['user'] = $this->userPayload($user, $request->user());

        return $array;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $user = $this->scopedTo($request->user())->with('protocolUnits.unit:id,parent_id,tipo,codigo,nome,ativo')->find($id);

        return $user ? $this->userPayload($user, $request->user()) : ['error' => '404'];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $user)
    {
        $this->authorizeTarget($request, $user);
        $previousProfile = $user->profile;
        $array = ['status' => 'updated'];
        $isAdmin = $request->user()->profile === 'admin';
        // Gestor altera apenas perfil e vínculos (R10): nada mais do request chega ao model.
        $data = $isAdmin ? $request->except(['password2', 'protocol_unit_ids', 'secretaria_ids']) : $request->only('profile');

        if ($isAdmin) {
            $data['password'] = $request->input('password') ? Hash::make($data['password']) : $user->password;
        }

        DB::transaction(function () use ($user, $data, $request, $isAdmin) {
            $user->update($data);
            if ($isAdmin) {
                $this->syncProtocolUnits($user, $request->input('protocol_unit_ids', []));
            }
            if ($request->has('secretaria_ids')) {
                $this->syncSecretarias($request->user(), $user, $request->input('secretaria_ids'));
            }
        });
        $this->alertIfGestorAssigned($request, $user, $previousProfile);
        $array['user'] = $this->userPayload($user->fresh(), $request->user());

        return $array;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, User $user)
    {
        // Inativar é global (vale em todas as secretarias): só admin.
        abort_unless($request->user()->profile === 'admin', 403, 'Somente administrador inativa usuário.');

        $array = ['status' => 'inactivated'];

        // User::where('id', $user)->update(['active' => 0, 'inactive_date' => new DateTime()]);
        $user->update(['active' => 0, 'inactive_date' => new DateTime()]);

        return $array;
    }

    private function syncProtocolUnits(User $user, array $unitIds): void
    {
        $unitIds = collect($unitIds)->map(fn ($id) => (int) $id)->unique()->values();

        $user->protocolUnits()->whereNotIn('protocol_organizational_unit_id', $unitIds)->update(['ativo' => false]);

        foreach ($unitIds as $unitId) {
            // Sem escopo: lotação existente em outra secretaria seria duplicada (unique user+unidade).
            \App\Models\ProtocolUserUnit::withoutGlobalScopes()->updateOrCreate(
                ['user_id' => $user->id, 'protocol_organizational_unit_id' => $unitId],
                ['papel' => 'lotacao', 'ativo' => true]
            );
        }
    }

    private function userPayload(User $user, User $actor): array
    {
        $user->loadMissing(['protocolUnits.unit:id,parent_id,tipo,codigo,nome,ativo', 'secretarias:secretarias.id,secretarias.nome']);

        return [
            ...$user->toArray(),
            'can_chat' => $user->canUseChat(),
            'secretarias' => $this->secretariasPayload($actor, $user, $actor->profile === 'admin' ? null : $this->actorSecretariaIds($actor)),
            'protocol_unit_ids' => $user->protocolUnits
                ->where('ativo', true)
                ->pluck('protocol_organizational_unit_id')
                ->values(),
        ];
    }
}
