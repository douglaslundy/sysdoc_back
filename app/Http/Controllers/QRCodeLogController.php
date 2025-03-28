<?php

namespace App\Http\Controllers;

use App\Models\QRCodeLog;
use Illuminate\Http\Request;

class QRCodeLogController extends Controller
{
    public function index()
    {
        $logs = QRCodeLog::with(['queue.client', 'queue.speciality'])->get();

        return response()->json($logs);
    }
}
