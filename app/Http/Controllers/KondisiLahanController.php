<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKondisiLahanRequest;
use App\Http\Requests\UpdateKondisiLahanRequest;
use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Models\RuleBaseLanjutan;
use App\Services\RbsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class KondisiLahanController extends Controller
{
    public function __construct(private RbsService $rbsService) {}

    public function index(Request $request)
    {
        $status = $request->query('status', 'semua');
        $statusValid = ['semua', 'belum', 'perlu-rekomendasi', 'sudah'];
        if (! in_array($status, $statusValid, true)) {
            $status = 'semua';
        }

        $allBloks = BlokLahan::with(['anggota', 'kondisiTerbaru', 'rekomendasiRbsTerbaru'])
            ->orderBy('anggota_id')
            ->orderBy('nama_blok')
            ->get();

        $stats = [
            'semua' => $allBloks->count(),
            'belum' => $allBloks->filter(fn ($blok) => ! $blok->kondisiTerbaru)->count(),
            'perlu_rekomendasi' => $allBloks->filter(function ($blok) {
                if (! $blok->kondisiTerbaru) {
                    return false;
                }

                $rekomendasi = $blok->rekomendasiRbsTerbaru;

                return ! $rekomendasi
                    || $blok->kondisiTerbaru->updated_at->gt($rekomendasi->updated_at)
                    || $this->recommendationNeedsObservation($rekomendasi);
            })->count(),
            'sudah' => $allBloks->filter(fn ($blok) => (bool) $blok->kondisiTerbaru)->count(),
        ];

        $filtered = $allBloks;

        if ($request->filled('anggota_id')) {
            $filtered = $filtered->where('anggota_id', (int) $request->anggota_id);
        }

        $filtered = match ($status) {
            'belum' => $filtered->filter(fn ($blok) => ! $blok->kondisiTerbaru),
            'perlu-rekomendasi' => $filtered->filter(function ($blok) {
                if (! $blok->kondisiTerbaru) {
                    return false;
                }

                $rekomendasi = $blok->rekomendasiRbsTerbaru;

                return ! $rekomendasi
                    || $blok->kondisiTerbaru->updated_at->gt($rekomendasi->updated_at)
                    || $this->recommendationNeedsObservation($rekomendasi);
            }),
            'sudah' => $filtered->filter(fn ($blok) => (bool) $blok->kondisiTerbaru),
            default => $filtered,
        };

        // Group by anggota — sort: terbaru di atas
        $grouped = $filtered->groupBy('anggota_id')->map(function ($bloks) {
            $anggota = $bloks->first()->anggota;

            return [
                'anggota' => $anggota,
                'bloks' => $bloks,
                'latest_activity' => $bloks->max(fn ($b) => max(
                    $b->updated_at?->timestamp ?? 0,
                    $b->kondisiTerbaru?->updated_at?->timestamp ?? 0
                )),
            ];
        })->sortByDesc('latest_activity')->values();

        $anggotas = Anggota::orderBy('nama')->get();

        return view('kondisi_lahan.index', compact('grouped', 'anggotas', 'stats', 'status'));
    }

    private function recommendationNeedsObservation(?RekomendasiRbs $rekomendasi): bool
    {
        return $rekomendasi
            && (in_array($rekomendasi->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
                || $rekomendasi->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA');
    }

    public function create(Request $request)
    {
        $bloks = $this->observationFormBlocks(true);
        $selectedBlokId = $request->query('blok_lahan_id');
        $bloksJson = $this->buildObservationBlocksJson($bloks);
        $leafConditions = $this->activeLeafConditions();

        return view('kondisi_lahan.create', compact('bloks', 'selectedBlokId', 'bloksJson', 'leafConditions'));
    }

    public function store(StoreKondisiLahanRequest $request)
    {
        $validated = $this->normalizeObservationData($request, $request->validated());
        $warnings = $this->validasiKonsistensi($validated);

        if ($request->hasFile('foto_observasi')) {
            $validated['foto_observasi_path'] = $request->file('foto_observasi')->store('observasi', 'public');
        }

        try {
            $kondisi = KondisiLahan::create($validated);
        } catch (\Throwable $exception) {
            if (! empty($validated['foto_observasi_path'])) {
                Storage::disk('public')->delete($validated['foto_observasi_path']);
            }
            throw $exception;
        }

        $blok = $kondisi->blokLahan;

        try {
            $this->rbsService->analisis($blok);
            $redirect = redirect()->route('rbs.detail', $blok)
                ->with('success', 'Observasi berhasil disimpan dan rekomendasi pupuk telah dihitung.');
        } catch (\Throwable $exception) {
            Log::warning('Analisis otomatis setelah observasi gagal.', [
                'blok_lahan_id' => $blok?->id,
                'kondisi_lahan_id' => $kondisi->id,
                'message' => $exception->getMessage(),
            ]);

            $redirect = redirect()->route('rbs.detail', $blok)
                ->with('warning', 'Observasi berhasil disimpan, tetapi rekomendasi belum dapat dihitung: '.$exception->getMessage());
        }

        if (! empty($warnings)) {
            $redirect = $redirect->with('warning', implode(' | ', $warnings));
        }

        return $redirect;
    }

    public function edit(KondisiLahan $kondisiLahan)
    {
        $bloks = $this->observationFormBlocks(false);
        $bloksJson = $this->buildObservationBlocksJson($bloks);
        $leafConditions = $this->activeLeafConditions();

        return view('kondisi_lahan.edit', compact('kondisiLahan', 'bloks', 'bloksJson', 'leafConditions'));
    }

    public function update(UpdateKondisiLahanRequest $request, KondisiLahan $kondisiLahan)
    {
        $validated = $this->normalizeObservationData($request, $request->validated());
        $warnings = $this->validasiKonsistensi($validated);
        $oldPhotoPath = $kondisiLahan->foto_observasi_path;
        $newPhotoPath = null;

        if ($request->hasFile('foto_observasi')) {
            $newPhotoPath = $request->file('foto_observasi')->store('observasi', 'public');
            $validated['foto_observasi_path'] = $newPhotoPath;
        } elseif ($request->boolean('hapus_foto')) {
            $validated['foto_observasi_path'] = null;
        }

        try {
            $kondisiLahan->update($validated);
        } catch (\Throwable $exception) {
            if ($newPhotoPath) {
                Storage::disk('public')->delete($newPhotoPath);
            }
            throw $exception;
        }

        if ($oldPhotoPath && array_key_exists('foto_observasi_path', $validated) && $oldPhotoPath !== $validated['foto_observasi_path']) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        $blok = $kondisiLahan->blokLahan;

        try {
            $this->rbsService->analisis($blok);
            $redirect = redirect()->route('rbs.detail', $blok)
                ->with('success', 'Observasi berhasil diperbarui dan rekomendasi pupuk telah dihitung ulang.');
        } catch (\Throwable $exception) {
            Log::warning('Analisis otomatis setelah pembaruan observasi gagal.', [
                'blok_lahan_id' => $blok?->id,
                'kondisi_lahan_id' => $kondisiLahan->id,
                'message' => $exception->getMessage(),
            ]);

            $redirect = redirect()->route('rbs.detail', $blok)
                ->with('warning', 'Observasi berhasil diperbarui, tetapi rekomendasi belum dapat dihitung ulang: '.$exception->getMessage());
        }

        if (! empty($warnings)) {
            $redirect = $redirect->with('warning', implode(' | ', $warnings));
        }

        return $redirect;
    }

    public function destroy(KondisiLahan $kondisiLahan)
    {
        if ($kondisiLahan->rekomendasiRbs()->exists()) {
            return redirect()->route('kondisi-lahan.index')
                ->with('error', 'Observasi tidak dapat dihapus karena sudah digunakan dalam analisis. Riwayat rekomendasi harus tetap dapat diaudit.');
        }

        $photoPath = $kondisiLahan->foto_observasi_path;
        $kondisiLahan->delete();

        if ($photoPath) {
            Storage::disk('public')->delete($photoPath);
        }

        return redirect()->route('kondisi-lahan.index')
            ->with('success', 'Data observasi berhasil dihapus.');
    }

    public function photo(KondisiLahan $kondisiLahan)
    {
        abort_unless(
            $kondisiLahan->foto_observasi_path
                && Storage::disk('public')->exists($kondisiLahan->foto_observasi_path),
            404
        );

        return Storage::disk('public')->response($kondisiLahan->foto_observasi_path);
    }

    private function observationFormBlocks(bool $latestFirst)
    {
        $query = BlokLahan::with('anggota')
            ->withMax([
                'realisasiPemupukans as tanggal_realisasi_terakhir' => fn ($query) => $query->where('status_realisasi', '!=', 'BATAL'),
            ], 'tanggal_realisasi');

        return $latestFirst
            ? $query->latest()->get()
            : $query->orderBy('nama_blok')->get();
    }

    private function buildObservationBlocksJson($bloks)
    {
        return $bloks->map(function ($blok) {
            $centroid = $this->hitungCentroid($blok->koordinat_geojson);

            return [
                'id' => $blok->id,
                'nama_blok' => $blok->nama_blok,
                'anggota_id' => $blok->anggota_id,
                'anggota_nama' => $blok->anggota?->nama ?? '-',
                'luas_ha' => $blok->luas_ha,
                'sph' => $blok->sph,
                'tahun_tanam' => $blok->tahun_tanam,
                'umur' => $blok->umur_tanaman,
                'fase' => $blok->fase_label,
                'kategori' => $blok->kategori_umur ?? '-',
                'tanggal_pemupukan_terakhir' => $blok->tanggal_realisasi_terakhir,
                'centroid_lat' => $centroid['lat'],
                'centroid_lng' => $centroid['lng'],
            ];
        })->values();
    }

    private function activeLeafConditions(): array
    {
        $configured = config('observation.diagnostic_leaf_conditions', []);
        $active = RuleBaseLanjutan::query()
            ->where('aktif', true)
            ->whereNotNull('kondisi_warna_daun')
            ->orderBy('prioritas')
            ->pluck('kondisi_warna_daun')
            ->filter(fn ($condition) => in_array($condition, $configured, true))
            ->unique()
            ->values()
            ->all();

        return array_values(array_unique(array_merge(
            [config('observation.normal_leaf_condition', 'Hijau Normal')],
            $active
        )));
    }

    private function normalizeObservationData(Request $request, array $validated): array
    {
        $validated['ada_gulma_dominan'] = $request->boolean('ada_gulma_dominan');
        $validated['ada_serangan_hama'] = $request->boolean('ada_serangan_hama');

        $leafValue = $validated['warna_daun'] ?? null;
        if (array_key_exists((string) $leafValue, config('observation.unmatched_leaf_values', []))) {
            $validated['warna_daun'] = null;
            $validated['status_verifikasi_gejala'] = 'perlu_konfirmasi';
        } elseif ($leafValue !== null) {
            $validated['status_verifikasi_gejala'] = 'terverifikasi';
        }

        $rainfallMode = $validated['metode_data_hujan'] ?? 'tidak_tersedia';
        if ($rainfallMode === 'perkiraan') {
            $validated['curah_hujan_mm_bulanan'] = null;
            $validated['periode_curah_hujan'] = null;
            $validated['sumber_curah_hujan'] = null;
        } elseif ($rainfallMode === 'tidak_tersedia') {
            $validated['curah_hujan_kategori'] = null;
            $validated['curah_hujan_mm_bulanan'] = null;
            $validated['periode_curah_hujan'] = null;
            $validated['sumber_curah_hujan'] = null;
        }

        // Pahan v2.6: Cegah manipulasi interval manual jika ada Realisasi resmi.
        // Sumber Kebenaran (Source of Truth) untuk pemupukan terakhir adalah RealisasiPemupukan.
        $latestRealisasi = RealisasiPemupukan::where('blok_lahan_id', $validated['blok_lahan_id'])
            ->where('status_realisasi', '!=', 'BATAL')
            ->latest('tanggal_realisasi')
            ->first();

        if ($latestRealisasi) {
            $validated['tanggal_pemupukan_terakhir'] = $latestRealisasi->tanggal_realisasi;
        }

        unset(
            $validated['anggota_id'],
            $validated['metode_data_hujan'],
            $validated['mode_data_hujan_dikonfirmasi'],
            $validated['foto_observasi'],
            $validated['hapus_foto']
        );

        foreach (['sumber_curah_hujan', 'periode_curah_hujan'] as $field) {
            if (($validated[$field] ?? null) === '') {
                $validated[$field] = null;
            }
        }

        return $validated;
    }

    /**
     * Hitung centroid (titik tengah) dari polygon GeoJSON.
     */
    private function hitungCentroid(?string $geojsonString): array
    {
        $default = ['lat' => null, 'lng' => null];

        if (! $geojsonString) {
            return $default;
        }

        try {
            $geojson = json_decode($geojsonString, true);
            if (! $geojson) {
                return $default;
            }

            $coords = null;
            if (($geojson['type'] ?? '') === 'Polygon') {
                $coords = $geojson['coordinates'][0] ?? null;
            } elseif (($geojson['type'] ?? '') === 'Feature') {
                $coords = $geojson['geometry']['coordinates'][0] ?? null;
            }

            if (! $coords || count($coords) < 3) {
                return $default;
            }

            $sumLat = 0;
            $sumLng = 0;
            $count = count($coords) - 1; // Exclude closing point

            for ($i = 0; $i < $count; $i++) {
                $sumLng += $coords[$i][0];
                $sumLat += $coords[$i][1];
            }

            return [
                'lat' => round($sumLat / $count, 6),
                'lng' => round($sumLng / $count, 6),
            ];
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Validasi konsistensi logis lintas-field (A4).
     * Tidak menggagalkan simpan, hanya return array warning.
     */
    private function validasiKonsistensi(array $data): array
    {
        $warnings = [];

        $musim = $data['musim_saat_ini'] ?? null;
        $kelembaban = $data['kelembaban_tanah'] ?? null;
        $curahHujan = $data['curah_hujan_kategori'] ?? null;
        $drainase = $data['kondisi_drainase'] ?? null;
        $warnaDaun = $data['warna_daun'] ?? null;
        $defisiensi = $data['gejala_defisiensi'] ?? [];

        // Musim kemarau tapi kelembaban tinggi
        if ($musim === 'Musim Kemarau' && in_array($kelembaban, ['Lembab', 'Sangat Lembab'])) {
            $warnings[] = 'Musim kemarau tapi kelembaban tinggi — mohon verifikasi data.';
        }

        // Musim hujan tapi kelembaban rendah
        if ($musim === 'Musim Hujan' && in_array($kelembaban, ['Kering', 'Sangat Kering'])) {
            $warnings[] = 'Musim hujan tapi kelembaban rendah — mohon verifikasi data.';
        }

        // Drainase tergenang tapi curah hujan sangat rendah
        if ($drainase === 'Buruk — Tergenang' && $curahHujan === 'Sangat Rendah') {
            $warnings[] = 'Drainase tergenang tapi curah hujan sangat rendah — kondisi ini jarang terjadi. Pastikan data sudah benar atau tambahkan catatan penjelasan.';
        }

        // Drainase tergenang tapi musim kemarau
        if ($drainase === 'Buruk — Tergenang' && $musim === 'Musim Kemarau') {
            $warnings[] = 'Drainase tergenang saat musim kemarau — situasi tidak lazim. Jika benar, mungkin ada masalah saluran drainase yang perlu dicatat.';
        }

        // Curah hujan sangat tinggi tapi kelembaban sangat kering
        if ($curahHujan === 'Sangat Tinggi' && in_array($kelembaban, ['Kering', 'Sangat Kering'])) {
            $warnings[] = 'Curah hujan sangat tinggi tapi kelembaban rendah — data ini kontradiktif, mohon verifikasi.';
        }

        // Curah hujan sangat rendah tapi kelembaban sangat lembab
        if ($curahHujan === 'Sangat Rendah' && in_array($kelembaban, ['Lembab', 'Sangat Lembab'])) {
            $warnings[] = 'Curah hujan sangat rendah tapi kelembaban tinggi — mohon verifikasi apakah ada sumber air lain.';
        }

        // Musim hujan tapi kelembaban sangat kering — mungkin data tidak sinkron
        // (validasi defisiensi vs warna daun dihapus karena gejala_defisiensi sudah tidak dipakai)

        // Musim hujan + curah hujan sangat rendah
        if ($musim === 'Musim Hujan' && $curahHujan === 'Sangat Rendah') {
            $warnings[] = 'Musim hujan tapi curah hujan sangat rendah — mohon pastikan data musim atau curah hujan sudah benar.';
        }

        // Musim kemarau + curah hujan sangat tinggi
        if ($musim === 'Musim Kemarau' && $curahHujan === 'Sangat Tinggi') {
            $warnings[] = 'Musim kemarau tapi curah hujan sangat tinggi — data ini tidak lazim, mohon verifikasi.';
        }

        return $warnings;
    }
}
