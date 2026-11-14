<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('profil', ['akun' => $request->user()->load('peran', 'pasien')]);
    }

    public function update(Request $request)
    {
        $akun = $request->user();
        $data = $request->validate([
            'email' => [$akun->pasien ? 'nullable' : 'required', 'email', Rule::unique('akun', 'email')->ignore($akun->id_akun, 'id_akun')],
            'no_tlp' => ['nullable', 'regex:/^(\+62|0)8[0-9]{8,12}$/'],
            'alamat' => ['nullable', 'string', 'max:500'],
            'password_lama' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['password' => 'kata sandi baru', 'password_lama' => 'kata sandi lama']);

        $akun->email = $data['email'];
        if (! empty($data['password'])) {
            $akun->password = $data['password'];
        }
        $akun->save();
        $akun->pasien?->update(array_filter(['no_tlp' => $data['no_tlp'] ?? null, 'alamat' => $data['alamat'] ?? null]));

        return $this->kembali('success', 'Profil diperbarui', 'Perubahan profil berhasil disimpan.');
    }
}

