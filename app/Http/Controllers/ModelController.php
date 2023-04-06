<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Models;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function index()
    {
        // return Letter::orderBy('id', 'desc')->get();
        return Models::with(['user'])->orderBy('id', 'desc')->get();
    }
    public function store(Request $request){return "not implemented";}
    public function update(Request $request){return "not implemented";}
    public function destroy($id){return "not implemented";}
}
