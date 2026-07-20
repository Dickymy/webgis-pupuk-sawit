<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use Illuminate\Http\Request;

class AnggotaController extends Controller
{
    public function index()
    {
        $anggotas = Anggota::withCount('blokLahans')->orderBy('nama')->paginate(10);

        return view('anggota.index', compact('anggotas'));
    }

    public function create()
    {
        return view('anggota.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:anggotas,nama'],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.required' => 'Nama anggota wajib diisi.',
            'nama.unique' => 'Nama anggota ini sudah terdaftar.',
        ]);

        Anggota::create($validated);

        return redirect()->route('anggota.index')->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit(Anggota $anggotum)
    {
        return view('anggota.edit', ['anggota' => $anggotum]);
    }

    public function update(Request $request, Anggota $anggotum)
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:100', 'unique:anggotas,nama,'.$anggotum->id],
            'no_hp' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.required' => 'Nama anggota wajib diisi.',
            'nama.unique' => 'Nama anggota ini sudah terdaftar.',
        ]);

        $anggotum->update($validated);

        return redirect()->route('anggota.index')->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Anggota $anggotum)
    {
        if ($anggotum->blokLahans()->exists()) {
            return redirect()->route('anggota.index')
                ->with('error', "Anggota '{$anggotum->nama}' tidak bisa dihapus karena masih memiliki blok lahan.");
        }

        $anggotum->delete();

        return redirect()->route('anggota.index')->with('success', 'Anggota berhasil dihapus.');
    }
}
