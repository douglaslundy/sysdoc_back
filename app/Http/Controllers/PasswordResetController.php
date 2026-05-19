<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class PasswordResetController extends Controller
{
    public function sendLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ], [
            'email.required' => 'O e-mail e obrigatorio.',
            'email.email' => 'Informe um e-mail valido.',
        ]);

        $user = User::where('email', $request->email)->where('active', true)->first();

        // Resposta generica para nao revelar se email existe
        if (! $user) {
            return response()->json([
                'message' => 'Se o e-mail estiver cadastrado, voce recebera o link de redefinicao.',
            ]);
        }

        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $resetUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'))
            .'/redefinir-senha?token='.$token.'&email='.urlencode($user->email);

        try {
            Mail::send('emails.password-reset', [
                'user' => $user,
                'resetUrl' => $resetUrl,
            ], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Redefinicao de Senha - SysDoc');
            });
        } catch (Throwable $e) {
            Log::error('Falha ao enviar e-mail de redefinicao de senha', [
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Nao foi possivel enviar o e-mail de redefinicao neste momento. Tente novamente em instantes.',
            ], 503);
        }

        return response()->json([
            'message' => 'Se o e-mail estiver cadastrado, voce recebera o link de redefinicao.',
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'email.required' => 'O e-mail e obrigatorio.',
            'email.email' => 'Informe um e-mail valido.',
            'token.required' => 'O token de redefinicao e obrigatorio.',
            'password.required' => 'A nova senha e obrigatoria.',
            'password.min' => 'A nova senha deve ter no minimo 8 caracteres.',
            'password.confirmed' => 'A confirmacao da nova senha nao confere.',
        ]);

        $registro = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (! $registro) {
            return response()->json(['message' => 'Token invalido ou expirado.'], 422);
        }

        $criacao = \Carbon\Carbon::parse($registro->created_at);
        if ($criacao->diffInMinutes(now()) > 60) {
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();

            return response()->json(['message' => 'Token expirado. Solicite um novo link.'], 422);
        }

        if (! Hash::check($request->token, $registro->token)) {
            return response()->json(['message' => 'Token invalido.'], 422);
        }

        $user = User::where('email', $request->email)->where('active', true)->first();
        if (! $user) {
            return response()->json(['message' => 'Usuario nao encontrado.'], 404);
        }

        $user->update(['password' => Hash::make($request->password)]);

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Senha redefinida com sucesso! Faca login.']);
    }
}
