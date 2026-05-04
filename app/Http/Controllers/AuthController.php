<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    public function unauthorized()
    {
        return response()->json(['error' => 'Não autorizado'], 401);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:100',
            'email'            => 'required|email|unique:users,email',
            'cpf'              => 'required|digits:11|unique:users,cpf',
            'password'         => 'required|string|min:8',
            'password_confirm' => 'required|same:password',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'cpf'      => $request->cpf,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'cpf'      => 'required|digits:11',
            'password' => 'required',
        ]);

        $user = User::where('cpf', $request->cpf)->where('active', true)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Usuário ou senha inválidos.'], 401);
        }

        $token = $user->createToken('token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => $this->userPayload($user),
        ], 201);
    }

    public function validateToken()
    {
        $user = auth()->user();

        return response()->json([
            'message' => 'Authenticated',
            'user'    => $this->userPayload($user),
        ]);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();

        return response()->json(['message' => 'Logout efetuado com sucesso.']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id'      => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'profile' => $user->profile,
        ];
    }
}
