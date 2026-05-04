<?php

namespace App\Http\Controllers;

use App\Models\ErrorLog;
use Illuminate\Http\Request;

class ErrorLogController extends Controller
{
    public function index(Request $request)
    {
        return ErrorLog::with('user')
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 50);
    }
}
