<?php

namespace App\Http\Controllers;

use App\Models\Log;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request)
    {
        return Log::with('user')
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 50);
    }
}
