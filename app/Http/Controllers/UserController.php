<?php

namespace App\Http\Controllers;

use App\Models\UserPresence;
use App\Http\Requests\UserRequest;
use App\Models\User;
use DateTime;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::where('active', true)->orderBy('id', 'desc')->get();
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
        $user = $request->all();
        $user['password'] = Hash::make($user['password']);
        User::create($user);
        $array['user'] = $user;

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
        return User::find($id) ? User::find($id) : ['error' => '404'];
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
        $data = $request->all();

        if ($request->input('password')) {

            $data['password'] = Hash::make($data['password']);
        } else {
            $data['password'] = $user->password;
        }

        $user->update($data);
        $array['user'] = $user;

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
}
