<?php

namespace App\Http\Controllers;

use App\Services\ServerMetrics;

class ServerController extends Controller
{
    // Laravel inyecta el service solo (inyección de dependencias)
    public function index(ServerMetrics $metrics)
    {
        return view('servidor', ['m' => $metrics->snapshot()]);
    }

    public function api(ServerMetrics $metrics)
    {
        return response()->json($metrics->snapshot());
    }
}