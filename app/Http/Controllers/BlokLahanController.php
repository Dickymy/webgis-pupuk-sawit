<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBlokLahanRequest;
use App\Http\Requests\UpdateBlokLahanRequest;
use App\Models\Anggota;
use App\Models\BlokLahan;
use Illuminate\Http\Request;

class BlokLahanController extends Controller
{
    public function index(Request $request)
    {
        $query = BlokLahan::with(['anggota', 'rekomendasiRbsTerbaru', 'kondisiTerbaru']);

        // Filter by anggota
        if ($request->filled('anggota_id')) {
            $query->where('anggota_id', $request->anggota_id);
        }

        // Filter by status RBS
        if ($request->filled('status')) {
            if ($request->status === 'Belum') {
                $query->whereDoesntHave('rekomendasiRbsTerbaru');
            } else {
                $query->whereHas('rekomendasiRbsTerbaru', function ($q) use ($request) {
                    $q->where('status_kebutuhan_dominan', $request->status);
                });
            }
        }

        $allFiltered = $query->latest()->get();

        // Group by anggota — sort: terbaru diupdate di atas
        $grouped = $allFiltered->groupBy('anggota_id')->map(function ($bloks) {
            $anggota = $bloks->first()->anggota;
            return [
                'anggota'         => $anggota,
                'bloks'           => $bloks,
                'latest_activity' => $bloks->max(fn($b) => $b->updated_at?->timestamp ?? 0),
            ];
        })->sortByDesc('latest_activity')->values();

        $anggotas = \App\Models\Anggota::orderBy('nama')->get();
        $totalBlok = BlokLahan::count();

        return view('blok_lahan.index', compact('grouped', 'anggotas', 'totalBlok'));
    }

    public function create()
    {
        $anggotas = Anggota::orderBy('nama')->get();
        $existingBloks = BlokLahan::select('id', 'nama_blok', 'koordinat_geojson')->get()
            ->map(fn($b) => ['nama' => $b->nama_blok, 'geojson' => json_decode($b->koordinat_geojson, true)])
            ->filter(fn($b) => $b['geojson'] !== null)->values();

        return view('blok_lahan.create', compact('anggotas', 'existingBloks'));
    }

    public function store(StoreBlokLahanRequest $request)
    {
        $validated = $request->validated();

        BlokLahan::create($validated);

        $redirect = redirect()->route('blok-lahan.index')->with('success', 'Blok lahan berhasil ditambahkan.');

        // Validasi SPH terhadap standar agronomis (C1)
        if ($validated['sph'] < 100 || $validated['sph'] > 160) {
            $redirect = $redirect->with('warning', "SPH yang dimasukkan ({$validated['sph']} pohon/ha) di luar rentang normal kelapa sawit (136–148 pohon/ha). Pastikan data sudah benar.");
        }

        return $redirect;
    }

    public function show(BlokLahan $blokLahan)
    {
        $blokLahan->load(['anggota', 'kondisiTerbaru', 'rekomendasiRbsTerbaru']);
        return view('blok_lahan.show', compact('blokLahan'));
    }

    public function edit(BlokLahan $blokLahan)
    {
        $anggotas = Anggota::orderBy('nama')->get();
        $existingBloks = BlokLahan::where('id', '!=', $blokLahan->id)
            ->select('id', 'nama_blok', 'koordinat_geojson')->get()
            ->map(fn($b) => ['nama' => $b->nama_blok, 'geojson' => json_decode($b->koordinat_geojson, true)])
            ->filter(fn($b) => $b['geojson'] !== null)->values();

        return view('blok_lahan.edit', compact('blokLahan', 'anggotas', 'existingBloks'));
    }

    public function update(UpdateBlokLahanRequest $request, BlokLahan $blokLahan)
    {
        $validated = $request->validated();

        $blokLahan->update($validated);

        $redirect = redirect()->route('blok-lahan.index')->with('success', 'Blok lahan berhasil diperbarui.');

        // Validasi SPH terhadap standar agronomis (C1)
        if ($validated['sph'] < 100 || $validated['sph'] > 160) {
            $redirect = $redirect->with('warning', "SPH yang dimasukkan ({$validated['sph']} pohon/ha) di luar rentang normal kelapa sawit (136–148 pohon/ha). Pastikan data sudah benar.");
        }

        return $redirect;
    }

    public function destroy(BlokLahan $blokLahan)
    {
        $blokLahan->delete();
        return redirect()->route('blok-lahan.index')->with('success', 'Blok lahan berhasil dihapus.');
    }
}
