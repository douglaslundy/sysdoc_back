<?php

namespace App\Http\Controllers;

use App\Models\UserPresence;
use App\Http\Requests\UserRequest;
use App\Models\User;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::with('protocolUnits.unit:id,parent_id,tipo,codigo,nome,ativo')
            ->where('active', true)
            ->orderBy('id', 'desc')
            ->get();
        $presences = UserPresence::query()->get()->keyBy('user_id');

        return $users->map(function (User $user) use ($presences) {
            $presence = $presences->get($user->id);
            $lastSeenAt = $presence?->last_seen_at;
            $isOnline = $lastSeenAt !== null && $lastSeenAt->greaterThanOrEqualTo(now()->subMinutes(5));

            return [
                ...$user->toArray(),
                'is_online' => $isOnline,
                'last_seen_at' => $lastSeenAt?->toDateTimeString(),
                'last_path' => $presence?->last_path,
                'can_chat' => $user->canUseChat(),
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
        $data = $request->except(['password2', 'protocol_unit_ids']);
        $data['password'] = Hash::make($data['password']);
        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create($data);
            $this->syncProtocolUnits($user, $request->input('protocol_unit_ids', []));
            return $user;
        });
        $array['user'] = $this->userPayload($user);

        return $array;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        // return User::where('active', true)->find($id) ? User::find($id) : ['error' => '404'];
        $user = User::with('protocolUnits.unit:id,parent_id,tipo,codigo,nome,ativo')->find($id);
        return $user ? $this->userPayload($user) : ['error' => '404'];
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, User $user)
    {
        $array = ['status' => 'updated'];
        $data = $request->except(['password2', 'protocol_unit_ids']);

        if ($request->input('password')) {

            $data['password'] = Hash::make($data['password']);
        } else {
            $data['password'] = $user->password;
        }

        DB::transaction(function () use ($user, $data, $request) {
            $user->update($data);
            $this->syncProtocolUnits($user, $request->input('protocol_unit_ids', []));
        });
        $array['user'] = $this->userPayload($user->fresh());

        return $array;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
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
            $user->protocolUnits()->updateOrCreate(
                ['protocol_organizational_unit_id' => $unitId],
                ['papel' => 'lotacao', 'ativo' => true]
            );
        }
    }

    private function userPayload(User $user): array
    {
        $user->loadMissing('protocolUnits.unit:id,parent_id,tipo,codigo,nome,ativo');

        return [
            ...$user->toArray(),
            'can_chat' => $user->canUseChat(),
            'protocol_unit_ids' => $user->protocolUnits
                ->where('ativo', true)
                ->pluck('protocol_organizational_unit_id')
                ->values(),
        ];
    }
}
