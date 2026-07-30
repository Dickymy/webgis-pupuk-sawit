<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveRuleBaseRequest;
use App\Models\RuleBaseLanjutan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RuleBaseController extends Controller
{
    private const JENIS_VISUAL = 'DIAGNOSIS_VISUAL';

    private const JENIS_WAKTU = 'PEMBATAS_APLIKASI';

    public function index(): View
    {
        $rules = RuleBaseLanjutan::query()
            ->where(function ($query) {
                $query->where('aktif', true)
                    ->orWhere(function ($pending) {
                        $pending->where('aktif', false)
                            ->where('status_validasi', '!=', 'NONAKTIF');
                    });
            })
            ->orderByDesc('aktif')
            ->orderBy('jenis_rule')
            ->orderBy('prioritas')
            ->get();

        return view('rule_base.index', compact('rules'));
    }

    public function info(): View
    {
        return view('rule_base.info');
    }

    public function create(): View
    {
        return view('rule_base.create', [
            'leafOptions' => config('observation.diagnostic_leaf_conditions'),
        ]);
    }

    public function store(SaveRuleBaseRequest $request): RedirectResponse
    {
        $rule = DB::transaction(function () use ($request) {
            $data = $this->normalizedRuleData($request->validated());

            return RuleBaseLanjutan::create($data + [
                'kode_rule' => $this->generateUniqueCode($data['jenis_rule']),
                'aktif' => false,
                'status_validasi' => 'PERLU_VALIDASI_AHLI',
                'versi_rule' => '1.0',
                'is_system_rule' => false,
            ]);
        });

        return redirect()
            ->route('rule-base.index')
            ->with('success', 'Rule berhasil disimpan dalam status belum digunakan. Periksa sumbernya, lalu pilih Gunakan jika sudah sesuai.');
    }

    public function edit(RuleBaseLanjutan $rule): View
    {
        $this->ensureManageable($rule);

        return view('rule_base.edit', [
            'rule' => $rule,
            'leafOptions' => config('observation.diagnostic_leaf_conditions'),
        ]);
    }

    public function update(SaveRuleBaseRequest $request, RuleBaseLanjutan $rule): RedirectResponse
    {
        $this->ensureManageable($rule);

        DB::transaction(function () use ($request, $rule) {
            $data = $this->normalizedRuleData($request->validated());
            $data['jenis_rule'] = $rule->jenis_rule;
            $data['versi_rule'] = $this->nextVersion($rule->versi_rule);
            $data['aktif'] = false;
            $data['status_validasi'] = 'PERLU_VALIDASI_AHLI';
            $data['divalidasi_oleh'] = null;
            $data['tanggal_validasi'] = null;

            $rule->update($data);
        });

        return redirect()
            ->route('rule-base.index')
            ->with('success', 'Perubahan disimpan sebagai versi '.$rule->fresh()->versi_rule.'. Rule dihentikan sementara agar sumber dan isinya dapat diperiksa sebelum digunakan kembali.');
    }

    public function toggleStatus(Request $request, RuleBaseLanjutan $rule): RedirectResponse
    {
        $this->ensureManageable($rule);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate'])],
        ]);

        if ($validated['action'] === 'deactivate') {
            $rule->update([
                'aktif' => false,
                'status_validasi' => 'PERLU_VALIDASI_AHLI',
            ]);

            return redirect()->route('rule-base.index')->with('success', 'Rule tidak digunakan lagi dalam analisis baru.');
        }

        if (! $this->hasCompleteSource($rule)) {
            return redirect()->route('rule-base.edit', $rule)
                ->with('warning', 'Lengkapi jenis dan nama sumber acuan sebelum menggunakan rule.');
        }

        if ($conflict = $this->findActiveConflict($rule)) {
            return redirect()->route('rule-base.index')->with('error', $conflict);
        }

        $rule->update([
            'aktif' => true,
            'status_validasi' => 'TERVERIFIKASI_SUMBER',
            'divalidasi_oleh' => auth('admin')->user()?->nama_lengkap,
            'tanggal_validasi' => now()->toDateString(),
        ]);

        return redirect()->route('rule-base.index')->with('success', 'Rule sekarang digunakan dalam analisis berikutnya.');
    }

    private function normalizedRuleData(array $data): array
    {
        $isVisual = $data['jenis_rule'] === self::JENIS_VISUAL;
        $fertilizer = $isVisual ? $data['jenis_pupuk_utama'] : 'Tidak ditentukan otomatis';
        $status = $data['status_kebutuhan'];

        return [
            'jenis_rule' => $data['jenis_rule'],
            'kondisi_warna_daun' => $isVisual ? $data['kondisi_warna_daun'] : null,
            'kondisi_curah_hujan_min_mm' => $isVisual ? null : ($data['kondisi_curah_hujan_min_mm'] ?? null),
            'kondisi_curah_hujan_max_mm' => $isVisual ? null : ($data['kondisi_curah_hujan_max_mm'] ?? null),
            'kondisi_ph_min' => null,
            'kondisi_ph_max' => null,
            'kondisi_kelembaban' => null,
            'kondisi_curah_hujan_kategori' => null,
            'kondisi_musim' => null,
            'kondisi_drainase' => null,
            'kondisi_defisiensi' => null,
            'kondisi_kategori_umur' => null,
            'kondisi_pelepah' => null,
            'kondisi_tandan' => null,
            'ada_serangan_hama' => null,
            'ada_gulma_dominan' => null,
            'kondisi_intermediate' => null,
            'prasyarat_intermediate' => null,
            'indikasi_masalah' => $data['indikasi_masalah'],
            'jenis_pupuk_utama' => $fertilizer,
            'jenis_pupuk_pendukung' => null,
            'dosis_anjuran' => $this->doseExplanation($data['jenis_rule'], $fertilizer, $status),
            'metode_aplikasi' => $isVisual && in_array($fertilizer, ['Urea', 'KCl'], true)
                ? 'Ikuti petunjuk aplikasi pada hasil rekomendasi.'
                : null,
            'waktu_aplikasi' => $this->timingExplanation($data['jenis_rule'], $status),
            'saran_tindakan' => $data['saran_tindakan'],
            'status_kebutuhan' => $status,
            'prioritas' => $data['prioritas'],
            'keterangan_rule' => $this->ruleSentence($data),
            'sumber_judul' => $data['sumber_judul'],
            'sumber_penulis' => $data['sumber_penulis'] ?? null,
            'sumber_tahun' => $data['sumber_tahun'] ?? null,
            'sumber_halaman' => $data['sumber_halaman'] ?? null,
            'sumber_tabel' => $data['sumber_tabel'] ?? null,
            'tingkat_bukti' => $data['tingkat_bukti'],
            'catatan_validasi' => $data['catatan_validasi'] ?? null,
            'tingkat_keparahan' => $isVisual ? $data['tingkat_keparahan'] : 'NORMAL',
            'kategori_kesimpulan' => $isVisual
                ? ($fertilizer === 'Tidak ditentukan otomatis' ? 'PERLU_PEMERIKSAAN' : 'GEJALA_DAUN')
                : ($status === 'Tunda' ? 'PEMUPUKAN_DITUNDA' : 'WAKTU_MENDUKUNG'),
        ];
    }

    private function doseExplanation(string $jenisRule, string $fertilizer, string $status): string
    {
        if ($jenisRule === self::JENIS_WAKTU) {
            return $status === 'Tunda'
                ? 'Dosis tidak diubah; waktu aplikasi ditunda.'
                : 'Dosis tetap mengikuti acuan Iyung Pahan (2013).';
        }

        return match ($fertilizer) {
            'Urea' => 'Kebutuhan Urea dihitung dari acuan Iyung Pahan (2013).',
            'KCl' => 'Kebutuhan KCl dihitung dari acuan Iyung Pahan (2013).',
            default => 'Tidak ada dosis otomatis dari pengamatan visual.',
        };
    }

    private function timingExplanation(string $jenisRule, string $status): string
    {
        if ($jenisRule === self::JENIS_VISUAL) {
            return 'Mengikuti hasil pemeriksaan kesiapan pemupukan.';
        }

        return $status === 'Tunda'
            ? 'Tunggu hingga curah hujan dan kondisi lahan mendukung.'
            : 'Dapat dijadwalkan jika kondisi lapangan dan interval juga terpenuhi.';
    }

    private function ruleSentence(array $data): string
    {
        if ($data['jenis_rule'] === self::JENIS_VISUAL) {
            return 'IF kondisi daun '.$data['kondisi_warna_daun'].' THEN '.$data['indikasi_masalah'].'.';
        }

        $min = $data['kondisi_curah_hujan_min_mm'] ?? null;
        $max = $data['kondisi_curah_hujan_max_mm'] ?? null;
        $range = match (true) {
            $min !== null && $max !== null => $min.'-'.$max.' mm/bulan',
            $min !== null => 'minimal '.$min.' mm/bulan',
            default => 'maksimal '.$max.' mm/bulan',
        };

        return 'IF curah hujan '.$range.' THEN '.$data['indikasi_masalah'].'.';
    }

    private function generateUniqueCode(string $jenisRule): string
    {
        $prefix = $jenisRule === self::JENIS_VISUAL ? 'VIS-CUSTOM-' : 'WAKTU-CUSTOM-';
        $numbers = RuleBaseLanjutan::query()
            ->where('kode_rule', 'like', $prefix.'%')
            ->pluck('kode_rule')
            ->map(function ($code) use ($prefix) {
                $suffix = substr((string) $code, strlen($prefix));

                return ctype_digit($suffix) ? (int) $suffix : 0;
            });

        return $prefix.str_pad((string) (($numbers->max() ?? 0) + 1), 3, '0', STR_PAD_LEFT);
    }

    private function nextVersion(?string $version): string
    {
        $value = is_numeric($version) ? (float) $version : 1.0;

        return number_format($value + 0.1, 1, '.', '');
    }

    private function hasCompleteSource(RuleBaseLanjutan $rule): bool
    {
        return filled($rule->sumber_judul)
            && filled($rule->tingkat_bukti);
    }

    private function findActiveConflict(RuleBaseLanjutan $rule): ?string
    {
        $activeRules = RuleBaseLanjutan::query()
            ->where('aktif', true)
            ->where('jenis_rule', $rule->jenis_rule)
            ->where('id', '!=', $rule->getKey())
            ->get();

        if ($rule->jenis_rule === self::JENIS_VISUAL) {
            $conflict = $activeRules->firstWhere('kondisi_warna_daun', $rule->kondisi_warna_daun);

            return $conflict
                ? 'Kondisi daun tersebut sudah digunakan oleh '.$conflict->kode_rule.'. Edit rule yang ada agar hasil tidak bertentangan.'
                : null;
        }

        $newMin = $rule->kondisi_curah_hujan_min_mm === null ? -INF : (float) $rule->kondisi_curah_hujan_min_mm;
        $newMax = $rule->kondisi_curah_hujan_max_mm === null ? INF : (float) $rule->kondisi_curah_hujan_max_mm;

        foreach ($activeRules as $activeRule) {
            $activeMin = $activeRule->kondisi_curah_hujan_min_mm === null ? -INF : (float) $activeRule->kondisi_curah_hujan_min_mm;
            $activeMax = $activeRule->kondisi_curah_hujan_max_mm === null ? INF : (float) $activeRule->kondisi_curah_hujan_max_mm;
            if ($newMin <= $activeMax && $activeMin <= $newMax) {
                return 'Rentang curah hujan bertumpang tindih dengan '.$activeRule->kode_rule.'. Sesuaikan batas agar hasil tidak bertentangan.';
            }
        }

        return null;
    }

    private function ensureManageable(RuleBaseLanjutan $rule): void
    {
        abort_if(! $rule->aktif && $rule->status_validasi === 'NONAKTIF', 404);
    }
}
