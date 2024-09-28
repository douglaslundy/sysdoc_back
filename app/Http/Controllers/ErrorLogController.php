<?php

// App\Http\Controllers\LogController.php

namespace App\Http\Controllers;

use App\Models\ErrorLog;

class ErrorLogController extends Controller
{
    public function index()
    {
        // Retorna os logs de erro ordenados por ID em ordem decrescente, limitando a 3000 registros
        return ErrorLog::with(['user'])
            ->orderBy('id', 'desc')
            ->take(3000)
            ->get();
    }
}
