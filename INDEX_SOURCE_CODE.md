# INDEX SOURCE CODE — Sistem Rekomendasi Pemupukan Kelapa Sawit (RBS)

> **Nama Proyek**: Aplikasi Skripsi — Sistem Pendukung Keputusan Pemupukan Kelapa Sawit Berbasis Rule-Based System  
> **Framework**: Laravel 11 (PHP 8.2+)  
> **Database**: MySQL  
> **Tanggal Generate**: 13 Juli 2026  

---

## DAFTAR ISI

1. [Struktur Proyek](#struktur-proyek)
2. [Konfigurasi](#konfigurasi)
3. [Routes](#routes)
4. [Controllers](#controllers)
5. [Models](#models)
6. [Services](#services)
7. [Middleware](#middleware)
8. [Providers](#providers)
9. [Database — Seeders](#database--seeders)
10. [Bootstrap](#bootstrap)

---

## Struktur Proyek

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AnggotaController.php
│   │   │   ├── AuthController.php
│   │   │   ├── BlokLahanController.php
│   │   │   ├── Controller.php
│   │   │   ├── CuacaController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── GeoUploadController.php
│   │   │   ├── KondisiLahanController.php
│   │   │   ├── LaporanController.php
│   │   │   ├── RbsController.php
│   │   │   └── RuleBaseController.php
│   │   └── Middleware/
│   │       └── AdminAuthenticated.php
│   ├── Models/
│   │   ├── Admin.php
│   │   ├── Anggota.php
│   │   ├── BlokLahan.php
│   │   ├── KondisiLahan.php
│   │   ├── RekomendasiRbs.php
│   │   └── RuleBaseLanjutan.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       └── RbsService.php
├── bootstrap/
│   ├── app.php
│   └── providers.php
├── config/
│   ├── auth.php
│   ├── database.php
│   └── ... (app, cache, filesystems, logging, mail, queue, services, session)
├── database/
│   ├── migrations/ (26 file migrasi)
│   └── seeders/
│       ├── AdminSeeder.php
│       ├── DatabaseSeeder.php
│       ├── RuleBaseLanjutanSeeder.php
│       └── RuleCurahHujanGulmaSeeder.php
├── resources/
│   ├── views/
│   │   ├── layouts/app.blade.php
│   │   ├── anggota/ (create, edit, index)
│   │   ├── auth/login.blade.php
│   │   ├── blok_lahan/ (create, edit, index, show)
│   │   ├── components/ (custom-select, filter-searchable, searchable-select, status-badge)
│   │   ├── dashboard/index.blade.php
│   │   ├── kondisi_lahan/ (create, edit, index)
│   │   ├── laporan/ (index, pdf, show)
│   │   ├── rbs/ (detail, index, partials/_hasil_rbs)
│   │   └── rule_base/ (create, edit, index, info)
│   ├── js/ (app.js, bootstrap.js, overlap-detector.js)
│   └── css/app.css
├── routes/
│   ├── web.php
│   └── console.php
├── tests/
│   └── sample_files/
├── docs/
│   ├── DFD_Level_0.drawio
│   ├── DFD_Level_1.drawio
│   └── referensi/
├── deploy/
│   ├── PANDUAN_DEPLOY_INFINITYFREE.md
│   └── PANDUAN_DEPLOY_RUMAHWEB.md
└── composer.json
```

---

## Konfigurasi

### composer.json

```json
{
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "require": {
        "php": "^8.2",
        "barryvdh/laravel-dompdf": "^3.1",
        "gasparesganga/php-shapefile": "3.4",
        "laravel/framework": "^11.0",
        "laravel/tinker": "^2.9"
    },
    "require-dev": {
        "fakerphp/faker": "^1.23",
        "laravel/pint": "^1.13",
        "laravel/sail": "^1.26",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.0",
        "phpunit/phpunit": "^10.5",
        "spatie/laravel-ignition": "^2.4"
    }
}
```

### config/auth.php

```php
<?php

return [
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'admin'),
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'),
    ],
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
    ],
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),
];
```

### config/database.php (MySQL)

```php
'default' => env('DB_CONNECTION', 'mysql'),

'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'url' => env('DB_URL'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'laravel'),
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => '',
        'strict' => true,
    ],
],
```

---

## Routes

### routes/web.php

```php
<?php

use App\Http\Controllers\AnggotaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlokLahanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KondisiLahanController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\RbsController;
use App\Http\Controllers\RuleBaseController;
use App\Http\Middleware\AdminAuthenticated;
use Illuminate\Support\Facades\Route;

// Root redirect
Route::get('/', fn() => redirect()->route('dashboard'));

// Authentication routes (guest only)
Route::middleware('guest:admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes — requires admin authentication
Route::middleware(AdminAuthenticated::class)->group(function () {

    // Dashboard (WebGIS)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Anggota Kelompok Tani
    Route::resource('anggota', AnggotaController::class)->except(['show']);

    // Manajemen Blok Lahan (termasuk kriteria agronomis)
    Route::resource('blok-lahan', BlokLahanController::class);

    // Kondisi Lahan
    Route::resource('kondisi-lahan', KondisiLahanController::class)->except(['show']);

    // Rule Base RBS
    Route::get('rule-base/info', [RuleBaseController::class, 'info'])->name('rule-base.info');
    Route::resource('rule-base', RuleBaseController::class)->except(['show']);

    // Analisis RBS (Rule-Based System)
    Route::prefix('rbs')->name('rbs.')->group(function () {
        Route::get('/', [RbsController::class, 'index'])->name('index');
        Route::get('/daftar-blok-belum-analisis', [RbsController::class, 'daftarBlokBelumAnalisis'])->name('daftarBlokBelumAnalisis');
        Route::post('/analisis/{blokLahan}', [RbsController::class, 'analisis'])->name('analisis');
        Route::post('/analisis-semua', [RbsController::class, 'analisisSemua'])->name('analisisSemua');
        Route::get('/detail/{blokLahan}', [RbsController::class, 'detail'])->name('detail');
    });

    // Laporan (berbasis rekomendasi RBS)
    Route::prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('/{rekomendasiRbs}/pdf', [LaporanController::class, 'exportPdf'])->name('pdf');
        Route::get('/{rekomendasiRbs}', [LaporanController::class, 'show'])->name('show');
    });

    // API endpoint — RBS popup WebGIS
    Route::get('/api/rbs-popup/{blokLahan}', [RbsController::class, 'apiPopup'])->name('api.rbs.popup');

    // API endpoint — Cuaca otomatis dari Open-Meteo
    Route::post('/api/cuaca/fetch', [\App\Http\Controllers\CuacaController::class, 'fetch'])->name('api.cuaca.fetch');

    // API endpoint — Upload SHP/GeoJSON ke GeoJSON polygon
    Route::post('/api/geo-upload', [\App\Http\Controllers\GeoUploadController::class, 'upload'])->name('api.geo.upload');
});
```

---

## Controllers

### app/Http/Controllers/Controller.php

```php
<?php

namespace App\Http\Controllers;

abstract class Controller
{
    //
}
```

### app/Http/Controllers/AuthController.php

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'username' => 'Username atau password yang Anda masukkan salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request)
    {
        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
```

### app/Http/Controllers/AnggotaController.php

```php
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
            'nama'   => ['required', 'string', 'max:100', 'unique:anggotas,nama'],
            'no_hp'  => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [
            'nama.required' => 'Nama anggota wajib diisi.',
            'nama.unique'   => 'Nama anggota ini sudah terdaftar.',
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
            'nama'   => ['required', 'string', 'max:100', 'unique:anggotas,nama,' . $anggotum->id],
            'no_hp'  => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:500'],
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
```

### app/Http/Controllers/BlokLahanController.php

```php
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

        if ($request->filled('anggota_id')) {
            $query->where('anggota_id', $request->anggota_id);
        }

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
        ]);

        json_decode($validated['koordinat_geojson']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['koordinat_geojson' => 'Format GeoJSON tidak valid.'])->withInput();
        }

        BlokLahan::create($validated);

        $redirect = redirect()->route('blok-lahan.index')->with('success', 'Blok lahan berhasil ditambahkan.');

        if ($validated['sph'] < 100 || $validated['sph'] > 160) {
            $redirect = $redirect->with('warning', "SPH yang dimasukkan ({$validated['sph']} pohon/ha) di luar rentang normal kelapa sawit (136–148 pohon/ha).");
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
        ]);

        json_decode($validated['koordinat_geojson']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return back()->withErrors(['koordinat_geojson' => 'Format GeoJSON tidak valid.'])->withInput();
        }

        $blokLahan->update($validated);
        return redirect()->route('blok-lahan.index')->with('success', 'Blok lahan berhasil diperbarui.');
    }

    public function destroy(BlokLahan $blokLahan)
    {
        $blokLahan->delete();
        return redirect()->route('blok-lahan.index')->with('success', 'Blok lahan berhasil dihapus.');
    }
}
```

### app/Http/Controllers/DashboardController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $blokLahans = BlokLahan::with([
            'anggota',
            'rekomendasiRbsTerbaru',
            'kondisiTerbaru',
        ])->get();

        $mapData = $blokLahans->map(function ($blok) {
            $rbs = $blok->rekomendasiRbsTerbaru;
            $statusDb = $rbs?->status_kebutuhan_dominan ?? 'Belum Dianalisis';

            return [
                'id'               => $blok->id,
                'nama_blok'        => $blok->nama_blok,
                'nama_pemilik'     => $blok->nama_pemilik,
                'luas_ha'          => $blok->luas_ha,
                'sph'              => $blok->sph,
                'umur_tanaman'     => $blok->umur_tanaman,
                'geojson'          => json_decode($blok->koordinat_geojson, true),
                'status_rbs'       => $statusDb,
                'status_label'     => \App\Models\RekomendasiRbs::labelStatus($statusDb),
                'masalah_rbs'      => $rbs?->masalah_teridentifikasi ?? [],
                'pupuk_rbs'        => $rbs?->rekomendasi_pupuk ?? [],
                'saran_rbs'        => $rbs?->saran_tindakan_utama ?? '',
                'tgl_analisis_rbs' => $rbs?->tanggal_analisis?->format('d/m/Y') ?? '-',
                'jumlah_rule'      => $rbs?->jumlah_rule_terpicu ?? 0,
                'dosis_urea'       => $rbs?->dosis_urea,
                'dosis_kcl'        => $rbs?->dosis_kcl,
                'total_urea'       => $rbs?->total_urea,
                'total_kcl'        => $rbs?->total_kcl,
            ];
        });

        $stats = [
            'total_blok'     => $blokLahans->count(),
            'total_luas'     => $blokLahans->sum('luas_ha'),
            'sudah_analisis' => $blokLahans->filter(fn($b) => $b->rekomendasiRbsTerbaru)->count(),
            'darurat'        => $blokLahans->filter(fn($b) => $b->rekomendasiRbsTerbaru?->status_kebutuhan_dominan === 'Darurat')->count(),
            'segera'         => $blokLahans->filter(fn($b) => $b->rekomendasiRbsTerbaru?->status_kebutuhan_dominan === 'Segera')->count(),
            'belum_kondisi'  => $blokLahans->filter(fn($b) => !$b->kondisiTerbaru)->count(),
        ];

        $bulanLalu = now()->subMonth();
        $rbsBulanLalu = RekomendasiRbs::where('tanggal_analisis', '>=', $bulanLalu->startOfMonth()->toDateString())
            ->where('tanggal_analisis', '<=', $bulanLalu->endOfMonth()->toDateString())
            ->get();

        $statsBulanLalu = [
            'darurat' => $rbsBulanLalu->where('status_kebutuhan_dominan', 'Darurat')->count(),
            'segera'  => $rbsBulanLalu->where('status_kebutuhan_dominan', 'Segera')->count(),
        ];

        $blokPerluPerhatian = $blokLahans->filter(function ($blok) {
            if ($blok->kondisiTerbaru && !$blok->rekomendasiRbsTerbaru) {
                return true;
            }
            if ($blok->rekomendasiRbsTerbaru && $blok->rekomendasiRbsTerbaru->tanggal_analisis->diffInDays(now()) > 90) {
                return true;
            }
            return false;
        })->values();

        return view('dashboard.index', compact('mapData', 'stats', 'statsBulanLalu', 'blokPerluPerhatian'));
    }
}
```

### app/Http/Controllers/CuacaController.php

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CuacaController extends Controller
{
    public function fetch(Request $request)
    {
        $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $lat = round((float) $request->lat, 2);
        $lng = round((float) $request->lng, 2);
        $cacheKey = "cuaca_{$lat}_{$lng}";

        try {
            $weatherData = Cache::remember($cacheKey, now()->addHours(12), function () use ($lat, $lng) {
                $response = Http::timeout(15)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude'      => $lat,
                    'longitude'     => $lng,
                    'past_days'     => 30,
                    'forecast_days' => 0,
                    'daily'         => 'precipitation_sum,et0_fao_evapotranspiration',
                    'timezone'      => 'auto',
                ]);

                if ($response->failed()) {
                    throw new \Exception('API Cuaca Open-Meteo gagal diakses. Status: ' . $response->status());
                }

                return $response->json();
            });

            $dailyPrecipitation = $weatherData['daily']['precipitation_sum'] ?? [];
            $dailyEt0           = $weatherData['daily']['et0_fao_evapotranspiration'] ?? [];

            if (empty($dailyPrecipitation) || empty($dailyEt0)) {
                throw new \Exception('Data cuaca tidak tersedia untuk koordinat ini.');
            }

            $totalPrecipitation = array_sum($dailyPrecipitation);
            $totalEt0           = array_sum($dailyEt0);
            $days               = count($dailyPrecipitation);
            $avgPrecipitation   = $totalPrecipitation / $days;

            $kategoriCurahHujan = $this->tentukanKategoriCurahHujan($avgPrecipitation);
            $musimSaatIni       = $this->tentukanMusimDinamis($totalPrecipitation, $totalEt0);

            return response()->json([
                'success'              => true,
                'curah_hujan_kategori' => $kategoriCurahHujan,
                'musim_saat_ini'       => $musimSaatIni,
                'detail' => [
                    'total_curah_hujan_mm'      => round($totalPrecipitation, 2),
                    'rata_rata_harian_mm'       => round($avgPrecipitation, 2),
                    'total_evapotranspirasi_mm' => round($totalEt0, 2),
                    'surplus_defisit_air_mm'    => round($totalPrecipitation - $totalEt0, 2),
                ],
            ]);
        } catch (\Exception $e) {
            Log::warning('Gagal auto-fetch cuaca: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal auto-fetch data iklim. Silakan isi form secara manual.',
            ], 200);
        }
    }

    private function tentukanKategoriCurahHujan(float $avgPerDay): string
    {
        if ($avgPerDay < 2.0) return 'Sangat Rendah';
        if ($avgPerDay <= 5.0) return 'Rendah';
        if ($avgPerDay <= 10.0) return 'Normal';
        if ($avgPerDay <= 15.0) return 'Tinggi';
        return 'Sangat Tinggi';
    }

    private function tentukanMusimDinamis(float $precipitation, float $et0): string
    {
        $et0Safeguard = $et0 > 0 ? $et0 : 1;
        $ratio = $precipitation / $et0Safeguard;

        if ($ratio < 0.8) return 'Musim Kemarau';
        if ($ratio > 1.2) return 'Musim Hujan';
        return 'Peralihan';
    }
}
```

### app/Http/Controllers/GeoUploadController.php

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Shapefile\ShapefileReader;

class GeoUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'geo_file' => ['required', 'file', 'max:10240'],
        ]);

        $file = $request->file('geo_file');
        $ext  = strtolower($file->getClientOriginalExtension());

        try {
            if ($ext === 'geojson' || $ext === 'json') {
                return $this->handleGeoJson($file);
            } elseif ($ext === 'zip') {
                return $this->handleShpZip($file);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Format file tidak didukung. Gunakan .zip (SHP) atau .geojson.',
                ], 422);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file: ' . $e->getMessage(),
            ], 422);
        }
    }

    private function handleGeoJson($file): \Illuminate\Http\JsonResponse
    {
        $content = file_get_contents($file->getRealPath());
        $geojson = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['success' => false, 'message' => 'File GeoJSON tidak valid.'], 422);
        }

        $polygon = $this->extractPolygon($geojson);
        if (!$polygon) {
            return response()->json(['success' => false, 'message' => 'Tidak ditemukan Polygon dalam file GeoJSON.'], 422);
        }

        return response()->json(['success' => true, 'geojson' => $polygon]);
    }

    private function handleShpZip($file): \Illuminate\Http\JsonResponse
    {
        $tempDir = storage_path('app/temp_shp_' . uniqid());
        mkdir($tempDir, 0755, true);

        try {
            $zip = new \ZipArchive();
            if ($zip->open($file->getRealPath()) !== true) {
                throw new \Exception('Gagal membuka file ZIP.');
            }
            $zip->extractTo($tempDir);
            $zip->close();

            $shpFile = $this->findFileWithExtension($tempDir, 'shp');
            if (!$shpFile) {
                throw new \Exception('File .shp tidak ditemukan dalam ZIP.');
            }

            $shapefile = new ShapefileReader($shpFile);
            $polygons = [];
            while ($geometry = $shapefile->fetchRecord()) {
                if ($geometry->isDeleted() || $geometry->isEmpty()) continue;
                $geoArray = json_decode($geometry->getGeoJSON(false), true);
                if (!$geoArray) continue;
                $type = $geoArray['type'] ?? '';
                if (in_array($type, ['Polygon', 'MultiPolygon'])) {
                    $polygons[] = $geoArray;
                }
            }
            unset($shapefile);

            if (empty($polygons)) {
                throw new \Exception('Tidak ditemukan data Polygon dalam Shapefile.');
            }

            $polygon = $polygons[0];
            if ($polygon['type'] === 'MultiPolygon') {
                $polygon = ['type' => 'Polygon', 'coordinates' => $polygon['coordinates'][0]];
            }

            return response()->json(['success' => true, 'geojson' => $polygon, 'total_shapes' => count($polygons)]);
        } finally {
            $this->deleteDirectory($tempDir);
        }
    }

    private function extractPolygon(array $geojson): ?array { /* ... recursive extraction ... */ }
    private function findFileWithExtension(string $dir, string $ext): ?string { /* ... recursive find ... */ }
    private function findCompanionFile(string $dir, string $baseName, string $ext): ?string { /* ... */ }
    private function deleteDirectory(string $dir): void { /* ... recursive delete ... */ }
}
```

### app/Http/Controllers/KondisiLahanController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use Illuminate\Http\Request;

class KondisiLahanController extends Controller
{
    public function index(Request $request)
    {
        $query = BlokLahan::with(['anggota', 'kondisiTerbaru', 'rekomendasiRbsTerbaru'])
            ->whereHas('kondisiLahans');

        if ($request->filled('anggota_id')) {
            $query->where('anggota_id', $request->anggota_id);
        }

        $bloksWithKondisi = $query->orderBy('anggota_id')->orderBy('nama_blok')->get();

        $grouped = $bloksWithKondisi->groupBy('anggota_id')->map(function ($bloks) {
            $anggota = $bloks->first()->anggota;
            return [
                'anggota' => $anggota,
                'bloks'   => $bloks,
                'latest_activity' => $bloks->max(fn($b) => $b->kondisiTerbaru?->updated_at?->timestamp ?? 0),
            ];
        })->sortByDesc('latest_activity')->values();

        $anggotas = \App\Models\Anggota::orderBy('nama')->get();
        return view('kondisi_lahan.index', compact('grouped', 'anggotas'));
    }

    public function create(Request $request)
    {
        $bloks = BlokLahan::with('anggota')->latest()->get();
        $anggotas = \App\Models\Anggota::orderBy('nama')->get();
        $selectedBlokId = $request->query('blok_lahan_id');

        $bloksJson = $bloks->map(function ($b) {
            $centroid = $this->hitungCentroid($b->koordinat_geojson);
            return [
                'id' => $b->id, 'nama_blok' => $b->nama_blok,
                'anggota_id' => $b->anggota_id, 'anggota_nama' => $b->anggota?->nama ?? '-',
                'luas_ha' => $b->luas_ha, 'kategori' => $b->kategori_umur ?? '-',
                'centroid_lat' => $centroid['lat'], 'centroid_lng' => $centroid['lng'],
            ];
        })->values();

        return view('kondisi_lahan.create', compact('bloks', 'anggotas', 'selectedBlokId', 'bloksJson'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'blok_lahan_id'              => ['required', 'exists:blok_lahans,id'],
            'tanggal_observasi'          => ['required', 'date'],
            'tanggal_pemupukan_terakhir' => ['nullable', 'date', 'before_or_equal:today'],
            'ph_tanah'                   => ['nullable', 'numeric', 'min:3', 'max:8'],
            'kelembaban_tanah'           => ['nullable', 'string'],
            'curah_hujan_kategori'       => ['nullable', 'string'],
            'musim_saat_ini'             => ['nullable', 'string'],
            'warna_daun'                 => ['nullable', 'string'],
            'kondisi_pelepah'            => ['nullable', 'string'],
            'gejala_defisiensi'          => ['nullable', 'array'],
            'kondisi_tandan'             => ['nullable', 'string'],
            'kondisi_drainase'           => ['nullable', 'string'],
            'ada_gulma_dominan'          => ['nullable', 'boolean'],
            'ada_serangan_hama'          => ['nullable', 'boolean'],
            'catatan_observasi'          => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['ada_gulma_dominan'] = $request->boolean('ada_gulma_dominan');
        $validated['ada_serangan_hama'] = $request->boolean('ada_serangan_hama');
        $validated['gejala_defisiensi'] = $validated['gejala_defisiensi'] ?? [];

        $warnings = $this->validasiKonsistensi($validated);
        KondisiLahan::create($validated);

        $redirect = redirect()->route('kondisi-lahan.index')
            ->with('success', 'Data kondisi lahan berhasil disimpan.');
        if (!empty($warnings)) {
            $redirect = $redirect->with('warning', implode(' | ', $warnings));
        }
        return $redirect;
    }

    public function edit(KondisiLahan $kondisiLahan) { /* ... load bloks & bloksJson with centroid ... */ }

    public function update(Request $request, KondisiLahan $kondisiLahan) { /* ... same validation as store ... */ }

    public function destroy(KondisiLahan $kondisiLahan)
    {
        $kondisiLahan->delete();
        return redirect()->route('kondisi-lahan.index')->with('success', 'Data kondisi lahan berhasil dihapus.');
    }

    private function hitungCentroid(?string $geojsonString): array
    {
        // Menghitung titik tengah polygon dari GeoJSON string
        // Return ['lat' => float, 'lng' => float]
    }

    private function validasiKonsistensi(array $data): array
    {
        // Validasi logika lintas-field (non-blocking warnings):
        // - Musim kemarau tapi kelembaban tinggi
        // - Musim hujan tapi kelembaban rendah
        // - Drainase tergenang tapi curah hujan sangat rendah
        // - Curah hujan sangat tinggi tapi kelembaban rendah
        // - Daun hijau normal tapi ada gejala defisiensi
        // dll.
    }
}
```

### app/Http/Controllers/RbsController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use App\Models\RuleBaseLanjutan;
use App\Services\RbsService;
use Illuminate\Http\Request;

class RbsController extends Controller
{
    public function __construct(private RbsService $rbsService) {}

    public function index(Request $request)
    {
        $query = BlokLahan::with(['anggota', 'kondisiTerbaru', 'rekomendasiRbsTerbaru']);

        if ($request->filled('anggota_id')) {
            $query->where('anggota_id', $request->anggota_id);
        }
        if ($request->filled('blok_lahan_id')) {
            $query->where('id', $request->blok_lahan_id);
        }

        $allFiltered = $query->orderBy('anggota_id')->orderBy('nama_blok')->get();

        $grouped = $allFiltered->groupBy('anggota_id')->map(function ($bloks) {
            $anggota = $bloks->first()->anggota;
            $latestActivity = $bloks->max(function ($b) {
                return max($b->updated_at?->timestamp ?? 0, $b->kondisiTerbaru?->created_at?->timestamp ?? 0);
            });
            return ['anggota' => $anggota, 'bloks' => $bloks, 'latest_activity' => $latestActivity];
        })->sortByDesc('latest_activity')->values();

        $anggotas = \App\Models\Anggota::orderBy('nama')->get();

        $allBloks = BlokLahan::with('rekomendasiRbsTerbaru', 'kondisiTerbaru')->get();
        $stats = [
            'total'          => $allBloks->count(),
            'sudah_analisis' => $allBloks->filter(fn($b) => $b->rekomendasiRbsTerbaru)->count(),
            'darurat'        => $allBloks->filter(fn($b) => $b->rekomendasiRbsTerbaru?->status_kebutuhan_dominan === 'Darurat')->count(),
            'segera'         => $allBloks->filter(fn($b) => $b->rekomendasiRbsTerbaru?->status_kebutuhan_dominan === 'Segera')->count(),
            'belum_kondisi'  => $allBloks->filter(fn($b) => !$b->kondisiTerbaru)->count(),
        ];

        $blokFilter = $request->filled('anggota_id')
            ? BlokLahan::where('anggota_id', $request->anggota_id)->orderBy('nama_blok')->get()
            : collect();

        return view('rbs.index', compact('grouped', 'anggotas', 'blokFilter', 'stats'));
    }

    public function analisis(BlokLahan $blokLahan)
    {
        try {
            $hasil = $this->rbsService->analisis($blokLahan);
            return redirect()->route('rbs.detail', $blokLahan)
                ->with('success', "Analisis RBS blok '{$blokLahan->nama_blok}' berhasil.");
        } catch (\Exception $e) {
            return redirect()->route('rbs.index')->with('error', $e->getMessage());
        }
    }

    public function analisisSemua()
    {
        $hasil    = $this->rbsService->analisisSemua();
        $berhasil = count($hasil['results']);
        $gagal    = count($hasil['errors']);

        $message = "Analisis selesai: {$berhasil} blok berhasil dianalisis.";
        if ($gagal > 0) {
            $message .= " {$gagal} blok gagal: " . implode('; ', $hasil['errors']);
        }
        return redirect()->route('rbs.index')->with($gagal > 0 ? 'warning' : 'success', $message);
    }

    public function detail(BlokLahan $blokLahan)
    {
        $blokLahan->load([
            'kondisiTerbaru',
            'kondisiLahans' => fn($q) => $q->latest('tanggal_observasi')->limit(5),
            'rekomendasiRbsTerbaru.kondisiLahan',
            'rekomendasiRbsTerbaru.admin',
        ]);

        $historiRekomendasi = RekomendasiRbs::where('blok_lahan_id', $blokLahan->id)
            ->where('is_latest', false)
            ->latest('tanggal_analisis')
            ->limit(20)
            ->get();

        return view('rbs.detail', compact('blokLahan', 'historiRekomendasi'));
    }

    public function apiPopup(BlokLahan $blokLahan)
    {
        $rbs = $blokLahan->rekomendasiRbsTerbaru;
        if (!$rbs) {
            return response()->json(['status' => 'Belum Dianalisis', 'masalah' => [], 'pupuk' => [], 'saran' => '']);
        }
        return response()->json([
            'status' => $rbs->status_kebutuhan_dominan,
            'warna_badge' => $rbs->warna_badge,
            'tanggal' => $rbs->tanggal_analisis->format('d/m/Y'),
            'masalah' => $rbs->masalah_teridentifikasi,
            'pupuk' => $rbs->rekomendasi_pupuk,
            'saran' => $rbs->saran_tindakan_utama,
            'jumlah_rule' => $rbs->jumlah_rule_terpicu,
        ]);
    }
}
```

### app/Http/Controllers/LaporanController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\Anggota;
use App\Models\BlokLahan;
use App\Models\RekomendasiRbs;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $query = RekomendasiRbs::with(['blokLahan.anggota', 'admin', 'kondisiLahan'])
            ->latest('tanggal_analisis');

        if (!$request->filled('histori') || $request->histori !== 'semua') {
            $query->where('is_latest', true);
        }
        if ($request->filled('status_kebutuhan_dominan')) {
            $query->where('status_kebutuhan_dominan', $request->status_kebutuhan_dominan);
        }
        if ($request->filled('anggota_id')) {
            $query->whereHas('blokLahan', fn($q) => $q->where('anggota_id', $request->anggota_id));
        }
        if ($request->filled('blok_lahan_id')) {
            $query->where('blok_lahan_id', $request->blok_lahan_id);
        }

        $rekap = $query->get();

        // Group by anggota — hitung subtotal per anggota (hanya blok layak pupuk)
        $grouped = $rekap->groupBy(fn($r) => $r->blokLahan->anggota_id ?? 0);
        $laporanPerAnggota = $grouped->map(function ($items) {
            $anggota = $items->first()->blokLahan->anggota;
            $blokLayak = $items->filter(fn($r) => in_array($r->status_kebutuhan_dominan, ['Normal', 'Segera']));
            return [
                'anggota' => $anggota, 'items' => $items,
                'subtotal_urea' => $blokLayak->sum('total_urea'),
                'subtotal_kcl'  => $blokLayak->sum('total_kcl'),
            ];
        })->values();

        // Grand total
        $rekapLayak = $rekap->filter(fn($r) => in_array($r->status_kebutuhan_dominan, ['Normal', 'Segera']));
        $totalUrea  = $rekapLayak->sum('total_urea');
        $totalKcl   = $rekapLayak->sum('total_kcl');
        $karungUrea = $totalUrea > 0 ? (int) ceil($totalUrea / 50) : 0;
        $karungKcl  = $totalKcl > 0 ? (int) ceil($totalKcl / 50) : 0;

        $anggotas = Anggota::orderBy('nama')->get();
        return view('laporan.index', compact('rekap', 'laporanPerAnggota', 'totalUrea', 'totalKcl', 'karungUrea', 'karungKcl', 'anggotas'));
    }

    public function show(RekomendasiRbs $rekomendasiRbs)
    {
        $rekomendasiRbs->load(['blokLahan.anggota', 'kondisiLahan', 'admin']);
        return view('laporan.show', compact('rekomendasiRbs'));
    }

    public function exportPdf(RekomendasiRbs $rekomendasiRbs)
    {
        $rekomendasiRbs->load(['blokLahan.anggota', 'kondisiLahan', 'admin']);
        $pdf = Pdf::loadView('laporan.pdf', compact('rekomendasiRbs'));
        $pdf->setPaper('a4', 'portrait');
        $filename = 'Laporan_' . str_replace(' ', '_', $rekomendasiRbs->blokLahan->nama_blok) . '_' . $rekomendasiRbs->tanggal_analisis->format('Y-m-d') . '.pdf';
        return $pdf->download($filename);
    }
}
```

### app/Http/Controllers/RuleBaseController.php

```php
<?php

namespace App\Http\Controllers;

use App\Models\RuleBaseLanjutan;
use Illuminate\Http\Request;

class RuleBaseController extends Controller
{
    public function index()
    {
        $rules = RuleBaseLanjutan::orderBy('prioritas')->orderBy('status_kebutuhan')->get();
        return view('rule_base.index', compact('rules'));
    }

    public function info() { return view('rule_base.info'); }
    public function create() { return view('rule_base.create'); }

    public function store(Request $request)
    {
        $validated = $this->validateRule($request);
        RuleBaseLanjutan::create($validated);
        return redirect()->route('rule-base.index')->with('success', 'Rule berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        $rule = RuleBaseLanjutan::findOrFail($id);
        return view('rule_base.edit', compact('rule'));
    }

    public function update(Request $request, string $id)
    {
        $rule = RuleBaseLanjutan::findOrFail($id);
        $validated = $this->validateRule($request);
        $rule->update($validated);
        return redirect()->route('rule-base.index')->with('success', 'Rule berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        RuleBaseLanjutan::findOrFail($id)->delete();
        return redirect()->route('rule-base.index')->with('success', 'Rule berhasil dihapus.');
    }

    private function validateRule(Request $request): array
    {
        $validated = $request->validate([
            'kondisi_warna_daun'           => ['nullable', 'string', 'max:100'],
            'kondisi_ph_min'               => ['nullable', 'numeric', 'min:3', 'max:8'],
            'kondisi_ph_max'               => ['nullable', 'numeric', 'min:3', 'max:8'],
            'kondisi_kelembaban'           => ['nullable', 'string', 'max:50'],
            'kondisi_curah_hujan_kategori' => ['nullable', 'string', 'max:50'],
            'kondisi_musim'                => ['nullable', 'string', 'max:50'],
            'kondisi_drainase'             => ['nullable', 'string', 'max:50'],
            'kondisi_defisiensi'           => ['nullable', 'string', 'max:50'],
            'kondisi_kategori_umur'        => ['nullable', 'string', 'max:50'],
            'kondisi_pelepah'              => ['nullable', 'string', 'max:100'],
            'kondisi_tandan'               => ['nullable', 'string', 'max:100'],
            'ada_serangan_hama'            => ['nullable'],
            'ada_gulma_dominan'            => ['nullable'],
            'indikasi_masalah'             => ['required', 'string', 'max:255'],
            'jenis_pupuk_utama'            => ['required', 'string', 'max:100'],
            'jenis_pupuk_pendukung'        => ['nullable', 'string', 'max:100'],
            'dosis_anjuran'                => ['required', 'string', 'max:150'],
            'metode_aplikasi'              => ['nullable', 'string', 'max:255'],
            'waktu_aplikasi'               => ['nullable', 'string', 'max:150'],
            'saran_tindakan'               => ['required', 'string', 'max:2000'],
            'status_kebutuhan'             => ['required', 'in:Darurat,Segera,Normal,Tunda'],
            'prioritas'                    => ['required', 'integer', 'min:1', 'max:10'],
            'aktif'                        => ['nullable'],
            'keterangan_rule'              => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['aktif'] = $request->boolean('aktif');
        $validated['ada_serangan_hama'] = $request->has('ada_serangan_hama')
            ? ($request->input('ada_serangan_hama') === 'null' ? null : $request->boolean('ada_serangan_hama'))
            : null;
        $validated['ada_gulma_dominan'] = $request->has('ada_gulma_dominan')
            ? ($request->input('ada_gulma_dominan') === 'null' ? null : $request->boolean('ada_gulma_dominan'))
            : null;

        foreach ($validated as $key => $value) {
            if (is_string($value) && trim($value) === '') $validated[$key] = null;
        }
        return $validated;
    }
}
```

---

## Models

### app/Models/Admin.php

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Admin extends Authenticatable
{
    use Notifiable;

    protected $fillable = ['username', 'password', 'nama_lengkap'];
    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
```

### app/Models/Anggota.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Anggota extends Model
{
    protected $fillable = ['nama', 'no_hp', 'alamat'];

    public function blokLahans(): HasMany
    {
        return $this->hasMany(BlokLahan::class, 'anggota_id');
    }

    public function getJumlahBlokAttribute(): int
    {
        return $this->blokLahans()->count();
    }
}
```

### app/Models/BlokLahan.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class BlokLahan extends Model
{
    protected $fillable = [
        'anggota_id', 'nama_blok', 'luas_ha', 'sph',
        'koordinat_geojson', 'tahun_tanam', 'jenis_tanah', 'topografi',
    ];

    protected function casts(): array
    {
        return ['luas_ha' => 'double', 'sph' => 'integer', 'tahun_tanam' => 'integer'];
    }

    public function anggota(): BelongsTo
    {
        return $this->belongsTo(Anggota::class, 'anggota_id');
    }

    public function kondisiLahans(): HasMany
    {
        return $this->hasMany(KondisiLahan::class, 'blok_lahan_id');
    }

    public function kondisiTerbaru(): HasOne
    {
        return $this->hasOne(KondisiLahan::class, 'blok_lahan_id')->latestOfMany('tanggal_observasi');
    }

    public function rekomendasiRbs(): HasMany
    {
        return $this->hasMany(RekomendasiRbs::class, 'blok_lahan_id');
    }

    public function rekomendasiRbsTerbaru(): HasOne
    {
        return $this->hasOne(RekomendasiRbs::class, 'blok_lahan_id')
            ->where('is_latest', true)
            ->latestOfMany('tanggal_analisis');
    }

    public function getNamaPemilikAttribute(): string
    {
        return $this->anggota?->nama ?? '-';
    }

    public function getUmurTanamanAttribute(): ?int
    {
        return $this->tahun_tanam ? (now()->year - $this->tahun_tanam) : null;
    }

    public function getKategoriUmurAttribute(): ?string
    {
        $umur = $this->umur_tanaman;
        if ($umur === null) return null;
        if ($umur < 3) return 'Belum Menghasilkan';
        if ($umur <= 8) return 'Remaja';
        if ($umur <= 14) return 'Menghasilkan Muda';
        if ($umur <= 25) return 'Menghasilkan Tua';
        return 'Tua Renta';
    }
}
```

### app/Models/KondisiLahan.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KondisiLahan extends Model
{
    protected $fillable = [
        'blok_lahan_id', 'tanggal_observasi', 'tanggal_pemupukan_terakhir',
        'ph_tanah', 'kelembaban_tanah', 'curah_hujan_kategori', 'musim_saat_ini',
        'warna_daun', 'kondisi_pelepah', 'gejala_defisiensi', 'kondisi_tandan',
        'kondisi_drainase', 'ada_gulma_dominan', 'ada_serangan_hama', 'catatan_observasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_observasi'          => 'date',
            'tanggal_pemupukan_terakhir' => 'date',
            'gejala_defisiensi'          => 'array',
            'ada_gulma_dominan'          => 'boolean',
            'ada_serangan_hama'          => 'boolean',
            'ph_tanah'                   => 'decimal:2',
        ];
    }

    public function blokLahan(): BelongsTo { return $this->belongsTo(BlokLahan::class); }
    public function rekomendasiRbs(): HasMany { return $this->hasMany(RekomendasiRbs::class); }

    public function getLabelPhAttribute(): string
    {
        if (is_null($this->ph_tanah)) return '-';
        return match(true) {
            $this->ph_tanah < 4.0  => 'Sangat Masam',
            $this->ph_tanah < 5.5  => 'Masam',
            $this->ph_tanah < 6.5  => 'Agak Masam (Optimal)',
            $this->ph_tanah < 7.5  => 'Netral',
            default                => 'Basa',
        };
    }
}
```

### app/Models/RekomendasiRbs.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RekomendasiRbs extends Model
{
    protected $table = 'rekomendasi_rbs';

    protected $fillable = [
        'blok_lahan_id', 'kondisi_lahan_id', 'admin_id', 'tanggal_analisis',
        'is_latest', 'nomor_analisis', 'rules_terpicu', 'masalah_teridentifikasi',
        'rekomendasi_pupuk', 'saran_tindakan_utama', 'status_kebutuhan_dominan',
        'jumlah_rule_terpicu', 'dosis_urea', 'dosis_kcl', 'total_urea', 'total_kcl',
        'catatan_dosis', 'jadwal_pemupukan', 'validitas_rekomendasi', 'catatan_validitas',
        'confidence_score', 'confidence_label', 'catatan_confidence',
        'data_cukup', 'data_kurang', 'notifikasi_data',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_analisis'        => 'date',
            'rules_terpicu'           => 'array',
            'masalah_teridentifikasi' => 'array',
            'rekomendasi_pupuk'       => 'array',
            'jadwal_pemupukan'        => 'array',
            'data_kurang'             => 'array',
            'is_latest'               => 'boolean',
            'data_cukup'              => 'boolean',
            'dosis_urea'              => 'double',
            'dosis_kcl'               => 'double',
            'total_urea'              => 'double',
            'total_kcl'               => 'double',
            'confidence_score'        => 'integer',
        ];
    }

    public function blokLahan(): BelongsTo { return $this->belongsTo(BlokLahan::class, 'blok_lahan_id'); }
    public function kondisiLahan(): BelongsTo { return $this->belongsTo(KondisiLahan::class, 'kondisi_lahan_id'); }
    public function admin(): BelongsTo { return $this->belongsTo(Admin::class, 'admin_id'); }

    public function getWarnaBadgeAttribute(): string
    {
        return match($this->status_kebutuhan_dominan) {
            'Darurat' => 'red', 'Segera' => 'orange', 'Normal' => 'green', 'Tunda' => 'gray', default => 'blue',
        };
    }

    public static function labelStatus(?string $status): string
    {
        return match($status) {
            'Darurat' => 'Defisiensi Berat',
            'Segera'  => 'Perlu Pupuk',
            'Normal'  => 'Sehat',
            'Tunda'   => 'Tunda Pupuk',
            default   => 'Belum Dicek',
        };
    }

    public function getKarungUreaAttribute(): int { return $this->total_urea ? (int) ceil($this->total_urea / 50) : 0; }
    public function getKarungKclAttribute(): int { return $this->total_kcl ? (int) ceil($this->total_kcl / 50) : 0; }

    public function getWarnaConfidenceAttribute(): string
    {
        return match($this->confidence_label) { 'Tinggi' => 'green', 'Sedang' => 'blue', default => 'amber' };
    }

    public function getWarnaValiditasAttribute(): string
    {
        return match($this->validitas_rekomendasi) { 'Terverifikasi' => 'green', 'Cukup Kuat' => 'blue', default => 'amber' };
    }
}
```

### app/Models/RuleBaseLanjutan.php

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RuleBaseLanjutan extends Model
{
    protected $table = 'rule_bases_lanjutan';

    protected $fillable = [
        'kondisi_warna_daun', 'kondisi_ph_min', 'kondisi_ph_max',
        'kondisi_kelembaban', 'kondisi_curah_hujan_kategori', 'kondisi_musim',
        'kondisi_drainase', 'kondisi_defisiensi', 'kondisi_kategori_umur',
        'kondisi_pelepah', 'kondisi_tandan', 'ada_serangan_hama', 'ada_gulma_dominan',
        'kondisi_intermediate', 'prasyarat_intermediate',
        'indikasi_masalah', 'jenis_pupuk_utama', 'jenis_pupuk_pendukung',
        'dosis_anjuran', 'metode_aplikasi', 'waktu_aplikasi', 'saran_tindakan',
        'status_kebutuhan', 'prioritas', 'aktif', 'keterangan_rule',
    ];

    protected function casts(): array
    {
        return [
            'aktif'                  => 'boolean',
            'ada_serangan_hama'      => 'boolean',
            'ada_gulma_dominan'      => 'boolean',
            'kondisi_ph_min'         => 'decimal:2',
            'kondisi_ph_max'         => 'decimal:2',
            'prioritas'              => 'integer',
            'kondisi_intermediate'   => 'array',
            'prasyarat_intermediate' => 'array',
        ];
    }

    public function scopeAktif($query)
    {
        return $query->where('aktif', true);
    }
}
```

---

## Services

### app/Services/RbsService.php

> File utama mesin analisis Rule-Based System (1114 baris).  
> Fitur: Forward Chaining, Rule Chaining, Confidence Score, Jadwal Pemupukan, Validitas Rekomendasi, Histori.

```php
<?php

namespace App\Services;

use App\Models\BlokLahan;
use App\Models\KondisiLahan;
use App\Models\RuleBaseLanjutan;
use App\Models\RekomendasiRbs;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RbsService
{
    private array $mappingVisualUnsur = [
        'Hijau Pucat'          => ['N'],
        'Kuning Merata'        => ['N', 'Zn'],
        'Kuning Tepi'          => ['K'],
        'Oranye/Kemerahan'     => ['K'],
        'Kuning Antar Tulang'  => ['Mg', 'Fe'],
        'Coklat Ujung'         => ['P', 'K'],
        'Bercak Nekrotik'      => ['K', 'P'],
    ];

    /**
     * Jalankan analisis RBS untuk satu blok lahan.
     */
    public function analisis(BlokLahan $blok): array
    {
        $kondisi = $blok->kondisiTerbaru;
        if (!$kondisi) {
            throw new \Exception("Data kondisi lahan belum tersedia untuk blok '{$blok->nama_blok}'.");
        }

        $kecukupanData = $this->cekKecukupanData($kondisi);

        if (!$this->kondisiCukup($kondisi)) {
            return $this->hasilDataTidakCukup($blok, $kondisi, $kecukupanData);
        }

        $kategoriUmur = $blok->kategori_umur;
        $rules = RuleBaseLanjutan::aktif()->orderBy('prioritas')->get();

        // Forward Chaining dengan Rule Chaining
        $rulesTerpicu = [];
        $intermediateFlags = [];

        foreach ($rules as $rule) {
            if (!$this->cekPrasyaratIntermediate($rule, $intermediateFlags)) continue;

            if ($this->evaluasiRule($rule, $kondisi, $kategoriUmur)) {
                $rulesTerpicu[] = $rule;
                if (!empty($rule->kondisi_intermediate) && is_array($rule->kondisi_intermediate)) {
                    $intermediateFlags = array_merge($intermediateFlags, $rule->kondisi_intermediate);
                }
            }
        }

        if (empty($rulesTerpicu)) {
            return $this->hasilNormal($blok, $kondisi, $kecukupanData);
        }

        return $this->susunHasil($blok, $kondisi, $rulesTerpicu, $kecukupanData);
    }

    public function analisisSemua(): array
    {
        $blokLahans = BlokLahan::whereHas('kondisiLahans')->with(['kondisiTerbaru'])->get();
        $results = []; $errors = [];
        foreach ($blokLahans as $blok) {
            try {
                $results[] = ['blok' => $blok, 'result' => $this->analisis($blok)];
            } catch (\Exception $e) {
                $errors[] = "Blok {$blok->nama_blok}: " . $e->getMessage();
            }
        }
        return ['results' => $results, 'errors' => $errors];
    }

    // ═══════════════════════════════════════════════════════════════
    // EVALUASI RULE (Forward Chaining — AND Logic)
    // ═══════════════════════════════════════════════════════════════

    private function evaluasiRule(RuleBaseLanjutan $rule, KondisiLahan $kondisi, ?string $kategoriUmur): bool
    {
        // Cek setiap kondisi di rule. NULL = diabaikan.
        // Semua kondisi yang diisi harus cocok (AND).
        // Kondisi yang dicek:
        //   warna_daun, ph_range, kelembaban, curah_hujan, musim,
        //   drainase, defisiensi, pelepah, serangan_hama, gulma_dominan,
        //   kondisi_tandan, kategori_umur
        // Return false jika ada ketidakcocokan. True jika semua cocok.
    }

    private function cekPrasyaratIntermediate(RuleBaseLanjutan $rule, array $intermediateFlags): bool
    {
        // Rule Chaining: cek apakah prasyarat intermediate terpenuhi
        if (empty($rule->prasyarat_intermediate)) return true;
        foreach ($rule->prasyarat_intermediate as $key => $value) {
            if (!isset($intermediateFlags[$key]) || $intermediateFlags[$key] !== $value) return false;
        }
        return true;
    }

    // ═══════════════════════════════════════════════════════════════
    // HITUNG DOSIS STANDAR (Referensi: Pahan 2015 & Fairhurst 2003)
    // ═══════════════════════════════════════════════════════════════

    private function hitungDosisStandar(BlokLahan $blok, ?KondisiLahan $kondisi = null): array
    {
        // Base dosis per kategori umur (kg/pokok/tahun):
        // TBM: Urea 0.75, KCl 0.75
        // Remaja: Urea 1.75, KCl 1.75
        // Menghasilkan Muda: Urea 2.25, KCl 2.25
        // Menghasilkan Tua: Urea 2.75, KCl 2.25
        // Tua Renta: Urea 1.75, KCl 1.75

        // Multiplier koreksi jenis tanah:
        // Lempung: 1.0/1.0, Berpasir: 1.25/1.35, Gambut: 0.7/1.5
        // Liat: 0.9/0.9, PMK/Laterit: 1.15/1.2

        // Multiplier koreksi topografi:
        // Datar: 1.0, Bergelombang: 1.1, Curam: 1.2

        // Multiplier koreksi waktu pemupukan terakhir:
        // < 60 hari: ×0.75, 60-120 hari: ×1.0, > 120 hari: ×1.25

        // Formula: dosis = baseDosis × multiplierTanah × multiplierTopo × multiplierWaktu
        // Total = dosis × SPH × luas_ha
    }

    // ═══════════════════════════════════════════════════════════════
    // CONFIDENCE SCORE (0-100)
    // ═══════════════════════════════════════════════════════════════

    private function hitungConfidence(KondisiLahan $kondisi, array $rulesTerpicu): array
    {
        // A. Kelengkapan Data: maks 40 poin (terisi/total × 40)
        // B. Jumlah Rule Terpicu: maks 25 poin (≥3=25, 2=18, 1=12, 0=5)
        // C. Kesesuaian Visual-Unsur: maks 20 poin
        // D. Penalti Kontradiksi: maks -20 poin
        // Label: ≥75 = Tinggi, ≥50 = Sedang, <50 = Rendah
    }

    // ═══════════════════════════════════════════════════════════════
    // VALIDITAS REKOMENDASI
    // ═══════════════════════════════════════════════════════════════

    private function tentukanValiditasRekomendasi(KondisiLahan $kondisi, array $kecukupanData): array
    {
        // Cukup Kuat: warna_daun + pH + (kelembaban/curah_hujan) + drainase
        // Estimasi Visual: data kurang lengkap
    }

    // ═══════════════════════════════════════════════════════════════
    // JADWAL PEMUPUKAN PER TAHAP
    // ═══════════════════════════════════════════════════════════════

    private function generateJadwalPemupukan(array $dataDosis, KondisiLahan $kondisi, string $statusDominan, BlokLahan $blok): array
    {
        // Status Tunda: 1 tahap (tunda + perbaiki lahan)
        // Status Darurat: 1 tahap (tunda kimia + koreksi lahan)
        // Status Normal/Segera:
        //   - Tahap Persiapan (jika ada gulma/hama)
        //   - Tahap 1A: Urea (pembagian[0]%)
        //   - Tahap 1B: KCl (pembagian[0]%, jeda 2-3 minggu)
        //   - Tahap 2A: Urea (pembagian[1]%, +6 bulan)
        //   - Tahap 2B: KCl (pembagian[1]%, jeda 2-3 minggu)
        // Pembagian: Darurat 70/30, Segera 60/40, Normal 50/50
    }

    // ═══════════════════════════════════════════════════════════════
    // HISTORI & SIMPAN
    // ═══════════════════════════════════════════════════════════════

    private function simpanDenganHistori(int $blokLahanId, array $data): RekomendasiRbs
    {
        // Cek apakah hasil sama dengan rekomendasi terakhir
        // Jika sama: hanya update tanggal_analisis
        // Jika beda: set semua lama is_latest=false, create baru dengan is_latest=true
    }

    private function susunHasil(BlokLahan $blok, KondisiLahan $kondisi, array $rules, array $kecukupanData): array
    {
        // Tentukan status dominan (hierarki: Tunda > Darurat > Segera > Normal)
        // Kumpulkan masalah unik
        // Kumpulkan rekomendasi pupuk (deduplicate)
        // Hitung dosis (Darurat/Tunda = 0)
        // Generate jadwal, validitas, confidence
        // Simpan dengan histori
    }
}
```

---

## Middleware

### app/Http/Middleware/AdminAuthenticated.php

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('admin')->check()) {
            return redirect()->route('login')->with('error', 'Silakan login terlebih dahulu.');
        }
        return $next($request);
    }
}
```

---

## Providers

### app/Providers/AppServiceProvider.php

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Notifikasi blok kritis ke semua view yang pakai layout app
        View::composer('layouts.app', function ($view) {
            $blokDarurat = \App\Models\BlokLahan::whereHas('rekomendasiRbsTerbaru', function ($q) {
                $q->where('status_kebutuhan_dominan', 'Darurat');
            })->with(['anggota', 'kondisiTerbaru', 'rekomendasiRbsTerbaru'])->get();

            $blokDarurat = $blokDarurat->filter(function ($blok) {
                $kondisi = $blok->kondisiTerbaru;
                $rbs = $blok->rekomendasiRbsTerbaru;
                if (!$kondisi || !$rbs) return false;
                return !$kondisi->updated_at->gt($rbs->updated_at);
            });

            $view->with('notifBlokDarurat', $blokDarurat->take(5));
            $view->with('jumlahNotifDarurat', $blokDarurat->count());
        });
    }
}
```

---

## Database — Seeders

### database/seeders/DatabaseSeeder.php

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            RuleBaseLanjutanSeeder::class,
        ]);
    }
}
```

### database/seeders/AdminSeeder.php

```php
<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        Admin::firstOrCreate(
            ['username' => 'admin'],
            ['password' => 'admin123', 'nama_lengkap' => 'Administrator']
        );
    }
}
```

### database/seeders/RuleBaseLanjutanSeeder.php

> Berisi 20 rule RBS untuk pemupukan kelapa sawit, mencakup:
> - Grup 1-2: Defisiensi Nitrogen (N) & Kalium (K)
> - Grup 3-4: Defisiensi Magnesium (Mg) & Boron (B)
> - Grup 5: pH Tanah Masam
> - Grup 6: Drainase Buruk
> - Grup 7: Kemarau Panjang
> - Grup 8-9: Tanaman Muda & Tua
> - Grup 10: Kondisi Normal
> - Grup 11-17: Defisiensi P, Serangan Hama, Fe, Zn
> - Grup 18-20: Pelepah Abnormal, Tandan, Musim Hujan

```php
<?php

namespace Database\Seeders;

use App\Models\RuleBaseLanjutan;
use Illuminate\Database\Seeder;

class RuleBaseLanjutanSeeder extends Seeder
{
    public function run(): void
    {
        if (RuleBaseLanjutan::count() > 0) {
            $this->command->info('RuleBaseLanjutan sudah ada, skip seeding.');
            return;
        }

        $rules = [
            // GRUP 1: DEFISIENSI NITROGEN (N)
            [
                'kondisi_warna_daun' => 'Kuning Merata',
                'kondisi_defisiensi' => 'N',
                'indikasi_masalah'   => 'Defisiensi Nitrogen — Klorosis Umum',
                'jenis_pupuk_utama'  => 'Urea (46% N)',
                'dosis_anjuran'      => '1.5–2.0 kg Urea/pokok, 2–3 kali/tahun',
                'status_kebutuhan'   => 'Segera',
                'prioritas'          => 2,
            ],
            // ... (20 rules total — lihat file seeder lengkap)

            // GRUP 3: DEFISIENSI KALIUM (K) — DARURAT
            [
                'kondisi_warna_daun' => 'Oranye/Kemerahan',
                'kondisi_defisiensi' => 'K',
                'indikasi_masalah'   => 'Defisiensi Kalium — Orange Frond (OF)',
                'jenis_pupuk_utama'  => 'KCl (60% K2O)',
                'dosis_anjuran'      => '2.0–2.5 kg KCl/pokok, 2 kali/tahun',
                'status_kebutuhan'   => 'Darurat',
                'prioritas'          => 1,
            ],

            // GRUP 5: pH SANGAT MASAM — DARURAT
            [
                'kondisi_ph_min'    => 3.0,
                'kondisi_ph_max'    => 4.5,
                'indikasi_masalah'  => 'pH Sangat Masam — Penghambatan Penyerapan Unsur Hara',
                'jenis_pupuk_utama' => 'Dolomit (Kapur Pertanian)',
                'dosis_anjuran'     => '500–1000 kg Dolomit/Ha, 1–2 kali/tahun',
                'status_kebutuhan'  => 'Darurat',
                'prioritas'         => 1,
            ],

            // GRUP 6: DRAINASE BURUK — TUNDA
            [
                'kondisi_drainase'  => 'Buruk — Tergenang',
                'indikasi_masalah'  => 'Waterlogging — Akar Kekurangan Oksigen dan Leaching Hara',
                'jenis_pupuk_utama' => 'MOP / KCl (melalui jalur daun — foliar)',
                'status_kebutuhan'  => 'Tunda',
                'prioritas'         => 1,
            ],

            // GRUP 10: KONDISI NORMAL
            [
                'kondisi_warna_daun' => 'Hijau Normal',
                'kondisi_ph_min'     => 5.5,
                'kondisi_ph_max'     => 6.5,
                'kondisi_drainase'   => 'Baik',
                'indikasi_masalah'   => 'Kondisi Optimal — Pemupukan Standar',
                'jenis_pupuk_utama'  => 'Urea + KCl (program rutin)',
                'status_kebutuhan'   => 'Normal',
                'prioritas'          => 9,
            ],
        ];

        foreach ($rules as $rule) {
            RuleBaseLanjutan::create($rule);
        }
    }
}
```

### database/seeders/RuleCurahHujanGulmaSeeder.php

```php
<?php

namespace Database\Seeders;

use App\Models\RuleBaseLanjutan;
use Illuminate\Database\Seeder;

class RuleCurahHujanGulmaSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            [
                'kondisi_curah_hujan_kategori' => 'Sangat Tinggi',
                'indikasi_masalah'             => 'Curah hujan sangat tinggi berisiko menyebabkan pencucian hara',
                'jenis_pupuk_utama'            => 'Tunda Pemupukan',
                'status_kebutuhan'             => 'Tunda',
                'prioritas'                    => 1,
            ],
            [
                'kondisi_curah_hujan_kategori' => 'Sangat Rendah',
                'indikasi_masalah'             => 'Curah hujan sangat rendah menyebabkan pupuk kurang efektif',
                'jenis_pupuk_utama'            => 'Tunda Pemupukan',
                'status_kebutuhan'             => 'Tunda',
                'prioritas'                    => 2,
            ],
            [
                'ada_gulma_dominan'    => true,
                'indikasi_masalah'     => 'Gulma dominan bersaing menyerap hara',
                'jenis_pupuk_utama'    => 'Pengendalian Gulma',
                'status_kebutuhan'     => 'Segera',
                'prioritas'            => 3,
            ],
        ];

        foreach ($rules as $rule) {
            RuleBaseLanjutan::create($rule);
        }
    }
}
```

---

## Bootstrap

### bootstrap/app.php

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'auth.admin' => \App\Http\Middleware\AdminAuthenticated::class,
        ]);
        $middleware->redirectGuestsTo('/login');
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

### bootstrap/providers.php

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
];
```

---

## Ringkasan Fitur Aplikasi

| No | Fitur | Deskripsi |
|----|-------|-----------|
| 1 | **WebGIS Dashboard** | Peta interaktif Leaflet menampilkan blok lahan dengan warna status RBS |
| 2 | **Manajemen Anggota** | CRUD anggota kelompok tani |
| 3 | **Manajemen Blok Lahan** | CRUD + upload GeoJSON/SHP + kriteria agronomis (tahun tanam, jenis tanah, topografi) |
| 4 | **Kondisi Lahan** | Input observasi lapangan (warna daun, pH, kelembaban, curah hujan, musim, dll) |
| 5 | **Auto-Fetch Cuaca** | Integrasi Open-Meteo API untuk curah hujan & musim otomatis (Neraca Air) |
| 6 | **Rule-Based System** | Forward Chaining + Rule Chaining dengan 20+ rule aktif |
| 7 | **Confidence Score** | Skor keyakinan 0–100 berdasarkan kelengkapan data & konsistensi |
| 8 | **Validitas Rekomendasi** | Cukup Kuat / Estimasi Visual berdasarkan kelengkapan parameter |
| 9 | **Jadwal Pemupukan** | Pembagian tahap berdasarkan status (Darurat/Segera/Normal/Tunda) |
| 10 | **Histori Rekomendasi** | Setiap analisis disimpan sebagai histori; duplikat hanya update tanggal |
| 11 | **Perhitungan Dosis** | Urea & KCl berdasarkan umur, jenis tanah, topografi, waktu pupuk terakhir |
| 12 | **Laporan & Export PDF** | Rekap per anggota, grand total pupuk, export PDF per blok |
| 13 | **Notifikasi Darurat** | Badge navbar untuk blok status "Darurat" |
| 14 | **Upload GeoJSON/SHP** | Import batas lahan dari file geospasial |

---

*File ini di-generate otomatis sebagai dokumentasi index source code proyek.*
