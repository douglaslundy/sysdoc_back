<?php

namespace App\Http\Controllers;

use App\Models\IluminacaoPoste;

class IluminacaoPosteController extends Controller
{
    public function index()
    {
        return IluminacaoPoste::query()->ativos()->latest()->paginate(15);
    }
}
