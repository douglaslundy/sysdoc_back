<?php

namespace App\Http\Controllers;

use App\Models\QRCodeLog;
use App\Support\OptionalPagination;
use Illuminate\Http\Request;

class QRCodeLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = OptionalPagination::apply(
            QRCodeLog::with(['queue.client', 'queue.speciality'])->orderBy('accessed_at', 'desc')->orderByDesc('id'),
            $request
        );

        return response()->json($logs);
    }
}
