<?php

namespace App\Http\Controllers;

use App\Models\QRCodeLog;
use Illuminate\Http\Request;

class QRCodeLogController extends Controller
{
    public function index()
    {
        $logs = QRCodeLog::with(['queue.client', 'queue.speciality'])->orderBy('accessed_at', 'desc')->get();
        

        return response()->json($logs);
    }
}
