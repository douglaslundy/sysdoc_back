<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

use App\Models\User;

class AuthController extends Controller
{
    public function unauthorized(){
        return response()->json([
            'error' => 'Não autorizado'
        ], 401);
    }

    public function register(Request $request){
        $array = ['error'=> ''];

        $validator = Validator::make($request->all(), [
        'name'=>'required',
        'email'=>'required|email|unique:users,email',
        'cpf'=>'required|digits:11|unique:users,cpf',
        'password'=>'required',
        'password_confirm'=>'required|same:password'
    ]);

    if(!$validator->fails()){

        $cpf = $request->input('cpf');
        $password = $request->input('password');

        $hash = password_hash($password, PASSWORD_DEFAULT);

        $newUser = new User();
        $newUser->name = $request->input('name');
        $newUser->email = $request->input('email');
        $newUser->cpf = $cpf;
        $newUser->password = $hash;
        $newUser->save();

        $token = auth()->attempt([
            'cpf'=>$cpf,
            'password'=>$password
        ]);

        if(!$token){
            $array['error'] = 'ocorreu um erro';
            return $array;
        }

        $array['token'] = $token;

        $user = auth()->user();
        $array['user'] = $user;


    } else{
        $array['errors'] = $validator->errors()->first();
        return $array;
    }

    return $array;
    }

    public function login(Request $request)
    {
        $request->validate([
            'cpf' => 'required|digits:11',
            'password' => 'required'
        ]);

        $user = User::where('cpf', $request->cpf)->where('active', true)->first();

        //valida e checa usuario e password

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => 'Usuario ou senha Inválidos'
            ], 401);
        }
        $token = $user->createToken('token')->plainTextToken;

        $response = [
            'user' => $user,
            'token' => $token
        ];

        return response($response, 201);
    }

    public function validateToken(){
        $array = ['error' => ''];
        $array = ['message' => 'Authenticated'];

        $user = auth()->user();
        $array['user'] = $user;

        return $array;
    }

    public function logout()
    {

        auth()->user()->tokens()->delete();

        return [
            'message' => 'Logout efetuado com sucesso '
        ];
    }
}
