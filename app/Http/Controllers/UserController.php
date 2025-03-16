<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\UserRequest;
use App\Models\User;
use DateTime;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;


class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // return User::orderBy('id', 'desc')->get();
        // return User::with(['company'])->where('active', true)->orderBy('id', 'desc')->get();
        return User::where('active', true)->orderBy('id', 'desc')->get();
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
     * @param  User  $user
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
     * @param  User  $user
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
