<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class OuvidoriaController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => []]);
    }
}
