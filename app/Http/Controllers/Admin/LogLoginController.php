<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LogLogin;
use Illuminate\Http\Request;

class LogLoginController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('admin.log-login', [
            'log' => LogLogin::with('akun.peran')->when($request->query('hasil'), fn ($q, $v) => $q->where('hasil', $v))
                ->latest('waktu')->paginate(30)->withQueryString(),
        ]);
    }
}

