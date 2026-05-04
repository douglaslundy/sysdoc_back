<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->where('active', true)->first();

        // Resposta genérica para não revelar se email existe
        if (!$user) {
            return response()->json([
                'message' => 'Se o e-mail estiver cadastrado, você receberá o link de redefinição.',
            ]);
        }

        // Gerar token seguro
        $token = Str::random(64);

        // Upsert do token (1 token por email)
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        // Enviar e-mail
        $resetUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'))
            . '/redefinir-senha?token=' . $token . '&email=' . urlencode($user->email);

        Mail::send('emails.password-reset', [
            'user'     => $user,
            'resetUrl' => $resetUrl,
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                    ->subject('Redefinição de Senha — SysDoc');
        });

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, você receberá o link de redefinição.',
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email'                 => 'required|email',
            'token'                 => 'required|string',
            'password'              => 'required|string|min:8|confirmed',
        ]);

        $registro = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$registro) {
            return response()->json(['message' => 'Token inválido ou expirado.'], 422);
        }

        // Verificar expiração (60 minutos)
        $criacao = \Carbon\Carbon::parse($registro->created_at);
        if ($criacao->diffInMinutes(now()) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Token expirado. Solicite um novo link.'], 422);
        }

        // Verificar token
        if (!Hash::check($request->token, $registro->token)) {
            return response()->json(['message' => 'Token inválido.'], 422);
        }

        // Atualizar senha
        $user = User::where('email', $request->email)->where('active', true)->first();
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);

        // Remover token usado
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Senha redefinida com sucesso! Faça login.']);
    }
}
