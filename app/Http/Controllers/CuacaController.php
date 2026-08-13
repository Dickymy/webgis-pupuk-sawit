<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CuacaController extends Controller
{
    /**
     * Ambil data cuaca dari Open-Meteo API berdasarkan koordinat centroid.
     * Menggunakan konsep Neraca Air (Water Balance) untuk penentuan musim dinamis.
     */
    public function fetch(Request $request)
    {
        // Validasi koordinat
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        // Pembulatan 2 desimal (~1.1 km presisi) untuk optimasi hit rate cache
        $lat = round((float) $request->lat, 2);
        $lng = round((float) $request->lng, 2);
        $cacheKey = "cuaca_{$lat}_{$lng}";

        try {
            // Cache selama 12 jam untuk mencegah rate-limit API Open-Meteo
            $weatherData = Cache::remember($cacheKey, now()->addHours(12), function () use ($lat, $lng) {
                // Request historical/past days forecast (timeout 15s untuk koneksi lambat/ngrok)
                $response = Http::timeout(15)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => $lat,
                    'longitude' => $lng,
                    'past_days' => 30,
                    'forecast_days' => 0, // Kita hanya butuh data historis 30 hari ke belakang
                    'daily' => 'precipitation_sum,et0_fao_evapotranspiration',
                    'timezone' => 'auto',
                ]);

                if ($response->failed()) {
                    throw new \Exception('API Cuaca Open-Meteo gagal diakses. Status: '.$response->status());
                }

                return $response->json();
            });

            // Ekstraksi data
            $dailyPrecipitation = $weatherData['daily']['precipitation_sum'] ?? [];
            $dailyEt0 = $weatherData['daily']['et0_fao_evapotranspiration'] ?? [];

            // Jika API merespons tapi datanya kosong (misal koordinat di laut lepas)
            if (empty($dailyPrecipitation) || empty($dailyEt0)) {
                throw new \Exception('Data cuaca tidak tersedia untuk koordinat ini.');
            }

            // Kalkulasi Agronomi
            $totalPrecipitation = array_sum($dailyPrecipitation);
            $totalEt0 = array_sum($dailyEt0);
            $days = count($dailyPrecipitation);
            $avgPrecipitation = $totalPrecipitation / $days;

            // Tentukan Kategori & Musim (Hybrid Mode: jadi nilai default untuk frontend)
            $kategoriCurahHujan = $this->tentukanKategoriCurahHujan($avgPrecipitation);
            $musimSaatIni = $this->tentukanMusimDinamis($totalPrecipitation, $totalEt0);

            return response()->json([
                'success' => true,
                'curah_hujan_kategori' => $kategoriCurahHujan,
                'curah_hujan_mm_bulanan' => round($totalPrecipitation, 1),
                'musim_saat_ini' => $musimSaatIni,
                'detail' => [
                    'total_curah_hujan_mm' => round($totalPrecipitation, 2),
                    'rata_rata_harian_mm' => round($avgPrecipitation, 2),
                    'total_evapotranspirasi_mm' => round($totalEt0, 2),
                    'surplus_defisit_air_mm' => round($totalPrecipitation - $totalEt0, 2),
                    'analisis' => 'Neraca air bulan lalu: P('.round($totalPrecipitation, 1).'mm) vs ET0('.round($totalEt0, 1).'mm)',
                ],
            ]);
        } catch (\Exception $e) {
            // Graceful Fallback: Log error tanpa membuat crash, frontend menangkap JSON ini
            // dan membiarkan user mengisi form secara manual (override).
            Log::warning('Gagal auto-fetch cuaca (Fallback Manual Activated): '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Gagal auto-fetch data iklim (timeout/error jaringan). Silakan isi form secara manual.',
                'error' => $e->getMessage(),
            ], 200); // HTTP 200 agar JS Axios tidak melempar Uncaught Promise Rejection
        }
    }

    /**
     * Tentukan Kategori Curah Hujan berdasarkan rata-rata harian (Standard BMKG diadaptasi).
     *
     * < 2 mm/hari  (~<60 mm/bulan)   = Sangat Rendah
     * 2-5 mm/hari  (~60-150 mm/bulan) = Rendah
     * 5-10 mm/hari (~150-300 mm/bulan)= Normal
     * 10-15 mm/hari(~300-450 mm/bulan)= Tinggi
     * > 15 mm/hari (~>450 mm/bulan)   = Sangat Tinggi
     */
    private function tentukanKategoriCurahHujan(float $avgPerDay): string
    {
        if ($avgPerDay < 2.0) {
            return 'Sangat Rendah';
        }
        if ($avgPerDay <= 5.0) {
            return 'Rendah';
        }
        if ($avgPerDay <= 10.0) {
            return 'Normal';
        }
        if ($avgPerDay <= 15.0) {
            return 'Tinggi';
        }

        return 'Sangat Tinggi';
    }

    /**
     * Tentukan Musim Aktual secara dinamis menggunakan Neraca Air (P vs ET0 ratio).
     *
     * Ratio P/ET0:
     * < 0.8  : Defisit air (Evapotranspirasi > Hujan) -> Musim Kemarau
     * > 1.2  : Surplus air (Hujan > Evapotranspirasi) -> Musim Hujan
     * 0.8-1.2: Seimbang -> Peralihan
     */
    private function tentukanMusimDinamis(float $precipitation, float $et0): string
    {
        // Mencegah division by zero jika terjadi anomali data
        $et0Safeguard = $et0 > 0 ? $et0 : 1;
        $ratio = $precipitation / $et0Safeguard;

        if ($ratio < 0.8) {
            return 'Musim Kemarau';
        } elseif ($ratio > 1.2) {
            return 'Musim Hujan';
        } else {
            return 'Peralihan';
        }
    }
}
