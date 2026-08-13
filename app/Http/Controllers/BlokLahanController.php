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
                    $q->where('status_kondisi_tanaman', $request->status);
                });
            }
        }

        $allFiltered = $query->latest()->get();

        // Group by anggota — sort: terbaru diupdate di atas
        $grouped = $allFiltered->groupBy('anggota_id')->map(function ($bloks) {
            $anggota = $bloks->first()->anggota;

            return [
                'anggota' => $anggota,
                'bloks' => $bloks,
                'latest_activity' => $bloks->max(fn ($b) => $b->updated_at?->timestamp ?? 0),
            ];
        })->sortByDesc('latest_activity')->values();

        $anggotas = Anggota::orderBy('nama')->get();
        $totalBlok = BlokLahan::count();

        return view('blok_lahan.index', compact('grouped', 'anggotas', 'totalBlok'));
    }

    public function create(Request $request)
    {
        $anggotas = Anggota::orderBy('nama')->get();
        $existingBloks = BlokLahan::select('id', 'nama_blok', 'koordinat_geojson')->get()
            ->map(fn ($b) => ['nama' => $b->nama_blok, 'geojson' => json_decode($b->koordinat_geojson, true)])
            ->filter(fn ($b) => $b['geojson'] !== null)->values();

        // Blok per anggota untuk panel ringkasan (JSON untuk JS)
        $bloksPerAnggota = BlokLahan::select('id', 'anggota_id', 'nama_blok', 'luas_ha')
            ->get()
            ->groupBy('anggota_id')
            ->map(fn ($bloks) => $bloks->map(fn ($b) => [
                'id' => $b->id,
                'nama_blok' => $b->nama_blok,
                'luas_ha' => (float) $b->luas_ha,
            ])->values())
            ->toArray();

        // Anggota yang sudah dipilih dari query param (misal dari link "Tambah Blok" di halaman show)
        $selectedAnggotaId = $request->query('anggota_id');

        return view('blok_lahan.create', compact('anggotas', 'existingBloks', 'bloksPerAnggota', 'selectedAnggotaId'));
    }

    public function store(StoreBlokLahanRequest $request)
    {
        $validated = $request->validated();

        // Fase otomatis untuk umur tidak ambigu (v2.3)
        $this->autoSetFase($validated);

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

        // Blok saudara: blok lain milik anggota yang sama
        $siblingBloks = BlokLahan::where('anggota_id', $blokLahan->anggota_id)
            ->where('id', '!=', $blokLahan->id)
            ->orderBy('nama_blok')
            ->get(['id', 'nama_blok', 'luas_ha']);

        return view('blok_lahan.show', compact('blokLahan', 'siblingBloks'));
    }

    public function edit(BlokLahan $blokLahan)
    {
        $anggotas = Anggota::orderBy('nama')->get();
        $existingBloks = BlokLahan::where('id', '!=', $blokLahan->id)
            ->select('id', 'nama_blok', 'koordinat_geojson')->get()
            ->map(fn ($b) => ['nama' => $b->nama_blok, 'geojson' => json_decode($b->koordinat_geojson, true)])
            ->filter(fn ($b) => $b['geojson'] !== null)->values();

        return view('blok_lahan.edit', compact('blokLahan', 'anggotas', 'existingBloks'));
    }

    public function update(UpdateBlokLahanRequest $request, BlokLahan $blokLahan)
    {
        $validated = $request->validated();

        // Fase otomatis untuk umur tidak ambigu (v2.3)
        $this->autoSetFase($validated);

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
        if ($blokLahan->rekomendasiRbs()->exists() || $blokLahan->programPemupukans()->exists() || $blokLahan->realisasiPemupukans()->exists()) {
            return redirect()->route('blok-lahan.index')
                ->with('error', 'Blok tidak dapat dihapus karena sudah memiliki histori rekomendasi atau realisasi. Data historis harus tetap dapat diaudit.');
        }

        $blokLahan->delete();

        return redirect()->route('blok-lahan.index')->with('success', 'Blok lahan berhasil dihapus.');
    }

    /**
     * Auto-set fase tanaman untuk umur yang tidak ambigu (v2.3).
     * umur < 3 → TBM otomatis
     * umur > 3 → TM otomatis
     * umur = 3 → pengguna wajib memilih (validasi di Request)
     */
    private function autoSetFase(array &$validated): void
    {
        $tahunTanam = $validated['tahun_tanam'] ?? null;
        if (! $tahunTanam) {
            return;
        }

        $umur = now()->year - (int) $tahunTanam;

        if ($umur < 3 && empty($validated['fase_tanaman'])) {
            $validated['fase_tanaman'] = 'TBM';
        } elseif ($umur > 3 && empty($validated['fase_tanaman'])) {
            $validated['fase_tanaman'] = 'TM';
        }
    }
}
