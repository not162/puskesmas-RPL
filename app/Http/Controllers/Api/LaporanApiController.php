<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\LaporanBulanan;
use Illuminate\Http\Request;

class LaporanApiController extends Controller
{
    public function show(Request $request)
    {
        return ['data' => (new LaporanBulanan($request->query('bulan')))->semua()];
    }
}
