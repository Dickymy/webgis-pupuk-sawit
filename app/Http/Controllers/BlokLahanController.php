<?php

namespace App\Http\Controllers;

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

    public function store(Request $request)
    {
        $validated = $request->validate([
            'anggota_id'        => ['required', 'exists:anggotas,id'],
            'nama_blok'         => ['required', 'string', 'max:100'],
            'luas_ha'           => ['required', 'numeric', 'min:0.01'],
            'sph'               => ['required', 'integer', 'min:1'],
            'koordinat_geojson' => ['required', 'string'],
            'tahun_tanam'       => ['required', 'integer', 'min:1990', 'max:' . now()->year],
            'jenis_tanah'       => ['required', 'in:Tanah Lempung,Tanah Lempung Berpasir,Tanah Berpasir,Tanah Liat,Tanah Gambut,Tanah Aluvial,Tanah Podsolik Merah Kuning (PMK),Tanah Laterit,Tanah Berbatu,Lainnya'],
            'topografi'         => ['required', 'in:Datar 0-15°,Bergelombang 15-30°,Curam >30°'],
            'fase_tanaman'      => ['nullable', 'in:TBM,TM'],
        ], [
            'anggota_id.required'        => 'Pemilik lahan wajib dipilih.',
            'nama_blok.required'         => 'Nama blok wajib diisi.',
            'luas_ha.required'           => 'Luas lahan wajib diisi.',
            'sph.required'               => 'SPH wajib diisi.',
            'koordinat_geojson.required' => 'Koordinat GeoJSON wajib diisi.',
            'tahun_tanam.required'       => 'Tahun tanam wajib diisi.',
            'jenis_tanah.required'       => 'Jenis tanah wajib dipilih.',
            'topografi.required'         => 'Topografi wajib dipilih.',
        ]);

        $geojson = json_decode($validated['koordinat_geojson'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['koordinat_geojson' => 'Format GeoJSON tidak valid.'])->withInput();
        }

        // Validasi struktur GeoJSON polygon
        $type = $geojson['type'] ?? null;
        $isValidGeometry = in_array($type, ['Polygon', 'MultiPolygon', 'Feature', 'FeatureCollection']);
        if (!$isValidGeometry) {
            return back()->withErrors(['koordinat_geojson' => 'GeoJSON harus berupa Polygon, MultiPolygon, Feature, atau FeatureCollection.'])->withInput();
        }

        // Validasi minimal: coordinates tidak kosong
        if ($type === 'Polygon' && (empty($geojson['coordinates']) || empty($geojson['coordinates'][0]) || count($geojson['coordinates'][0]) < 4)) {
            return back()->withErrors(['koordinat_geojson' => 'Polygon harus memiliki minimal 4 titik koordinat.'])->withInput();
        }

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

    public function update(Request $request, BlokLahan $blokLahan)
    {
        $validated = $request->validate([
            'anggota_id'        => ['required', 'exists:anggotas,id'],
            'nama_blok'         => ['required', 'string', 'max:100'],
            'luas_ha'           => ['required', 'numeric', 'min:0.01'],
            'sph'               => ['required', 'integer', 'min:1'],
            'koordinat_geojson' => ['required', 'string'],
            'tahun_tanam'       => ['required', 'integer', 'min:1990', 'max:' . now()->year],
            'jenis_tanah'       => ['required', 'in:Tanah Lempung,Tanah Lempung Berpasir,Tanah Berpasir,Tanah Liat,Tanah Gambut,Tanah Aluvial,Tanah Podsolik Merah Kuning (PMK),Tanah Laterit,Tanah Berbatu,Lainnya'],
            'topografi'         => ['required', 'in:Datar 0-15°,Bergelombang 15-30°,Curam >30°'],
            'fase_tanaman'      => ['nullable', 'in:TBM,TM'],
        ]);

        json_decode($validated['koordinat_geojson'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['koordinat_geojson' => 'Format GeoJSON tidak valid.'])->withInput();
        }

        // Validasi struktur GeoJSON polygon
        $geojson = json_decode($validated['koordinat_geojson'], true);
        $type = $geojson['type'] ?? null;
        if (!in_array($type, ['Polygon', 'MultiPolygon', 'Feature', 'FeatureCollection'])) {
            return back()->withErrors(['koordinat_geojson' => 'GeoJSON harus berupa Polygon, MultiPolygon, Feature, atau FeatureCollection.'])->withInput();
        }

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
