<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    /**
     * Exibe uma lista de logs com seus relacionamentos.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return Log::with(['user'])
            ->orderBy('id', 'desc')
            ->take(3000)
            ->get();
    }
}
