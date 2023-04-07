<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

use App\Models\User;
use Exception;

class UserController extends Controller
{
    public function index()
    {
        // $array = ['errors' => ''];

        // $array['users'] = User::all();
        // return $array;
        return User::orderBy('id', 'desc')->get();
    }

    public function store(Request $request)
    {
        $array = ['errors' => ''];

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|',
            'cpf' => 'required',
            'password' => 'required',
            //'arquivo' => 'required|mimes:jpg, png, pdf
        ]);

        if (!$validator->fails()) {

            //$file = $request->file('photo')->store('public');

            $user = new User();

            if ($request->input('password') !== $request->input('password2'))
                   throw new Exception('As senhas precisam ser iguais');

            $user->name = $request->input('name');
            $user->email = $request->input('email');
            $user->cpf = $request->input('cpf');
            $user->profile = $request->input('profile');
            $user->password = password_hash($request->input('password'), PASSWORD_DEFAULT);
            $user->save();
            $array['user'] = $user;
        } else {
            $array['errors'] = $validator->errors()->first();
                throw new Exception($array['errors']);
        }

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
        $array = ['errors' => ''];
        $array['users'] = User::find($id);
        return $array;
    }

    public function update(Request $request)
    {
        $array = ['errors' => ''];

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            //'arquivo' => 'required|mimes:jpg, png, pdf
        ]);

        $user = User::find($request->id);

        if (!$validator->fails()) {


            if (is_null($user)) {

                $array['errors'] = "user has not found";
            } else {

                $user->name = $request->input('name');

                if ($request->input('password')) {
                    $password = password_hash($request->input('password'), PASSWORD_DEFAULT);
                } else {
                    $password = $user->password;
                }

                $user->profile = $request->input('profile');
                $user->password = $password;
                $user->save();
                $array['user'] = $user;
            }
        } else {
            $array['errors'] = $validator->errors()->first();
            return $array;
        }

        return $array;
    }

    public function destroy($id)
    {
        $array = ['errors' => ''];

        $user = User::find($id);

        if (is_null($user)) {

            $array['errors'] = "user has not found";
        } else {

            $user->delete();
        }
        return $array;
    }
}
