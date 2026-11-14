<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotifikasiApiController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->notifications()->paginate(20)->through(fn ($n) => ['id' => $n->id, 'dibaca' => (bool) $n->read_at, 'waktu' => $n->created_at] + $n->data);
    }

    public function baca(Request $request, string $id)
    {
        $request->user()->notifications()->findOrFail($id)->markAsRead();
        return ['message' => 'Ditandai dibaca.'];
    }
}

