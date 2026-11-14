<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('admin.audit', [
            'objek' => AuditLog::distinct()->orderBy('objek')->pluck('objek'),
            'log' => AuditLog::with('akun.peran')
                ->when($request->query('objek'), fn ($q, $v) => $q->where('objek', $v))
                ->when($request->query('aksi'), fn ($q, $v) => $q->where('aksi', $v))
                ->latest('waktu')->paginate(30)->withQueryString(),
        ]);
    }
}

