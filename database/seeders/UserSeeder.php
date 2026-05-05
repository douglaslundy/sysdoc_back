<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->insertOrIgnore([
            'name'    => 'Douglas',
            'email'   => 'douglaslundy@gmail.com',
            'cpf'     => '08449222699',
            'password' => Hash::make('12345678'),
            'profile' => 'admin',
            'active'  => true,
        ]);
    }
}
