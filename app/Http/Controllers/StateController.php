<?php

namespace App\Http\Controllers;
use App\Models\State;

use Illuminate\Http\Request;

class StateController extends Controller
{
     public function index()
    {
        return response()->json(
            State::orderBy('name')->get(['code', 'name'])
        );
    }
}
