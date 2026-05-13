<?php

namespace App\Http\Controllers;

use App\Services\Pharmacy\PharmacyCatalogService;
use Illuminate\Http\JsonResponse;

class PharmacyCatalogController extends Controller
{
    public function __construct(private PharmacyCatalogService $service)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->service->all());
    }
}
