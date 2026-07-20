<?php

namespace App\Http\Requests;

use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\FertilizationRealizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi ketat untuk pencatatan realisasi pemupukan.
 *
 * Pahan v2.6:
 * - Validasi tahap sesuai status aktif
 * - Validasi interval 60 hari untuk Tahap 2
 * - Validasi override kebutuhan tahunan
 * - Konfirmasi jika melebihi rencana
 */
class StoreRealisasiPemupukanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rekomendasi_rbs_id' => ['required', 'integer', 'exists:rekomendasi_rbs,id'],
            'tahap' => ['required', 'integer', Rule::in([1, 2])],
            'tahun_program' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'tanggal_realisasi' => ['required', 'date', 'before_or_equal:today'],
            'urea_rencana_kg' => ['required', 'numeric', 'min:0'],
            'kcl_rencana_kg' => ['required', 'numeric', 'min:0'],
            'urea_realisasi_kg' => ['required', 'numeric', 'min:0'],
            'kcl_realisasi_kg' => ['required', 'numeric', 'min:0'],
            'status_realisasi' => ['required', 'string', Rule::in([
                RealisasiPemupukan::STATUS_SELESAI,
                RealisasiPemupukan::STATUS_SEBAGIAN,
                RealisasiPemupukan::STATUS_BATAL,
            ])],
            'catatan_pelaksana' => ['nullable', 'string', 'max:1000'],
            'confirmed_over_plan' => ['nullable', 'boolean'],
            'override_annual_limit' => ['nullable', 'boolean'],
            'override_reason' => ['nullable', 'required_if:override_annual_limit,true', 'string', 'max:1000'],
        ];
    }

    /**
     * Validasi tambahan setelah validasi dasar.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateStageLogic($validator);
            $this->validateOverPlanConfirmation($validator);
            $this->validateAnnualLimit($validator);
        });
    }

    /**
     * Validasi logika tahap.
     */
    private function validateStageLogic($validator): void
    {
        $rekomendasiId = $this->input('rekomendasi_rbs_id');
        $tahap = (int) $this->input('tahap');

        if (! $rekomendasiId) {
            return;
        }

        $rekomendasi = RekomendasiRbs::find($rekomendasiId);
        if (! $rekomendasi) {
            return;
        }

        $blok = $rekomendasi->blokLahan;
        if (! $blok) {
            return;
        }

        $realizationService = app(FertilizationRealizationService::class);
        $summary = $realizationService->getRealizationSummary($blok, $rekomendasiId);

        // Validasi Tahap 2: harus ada Tahap 1 selesai + interval 60 hari
        if ($tahap === 2) {
            if (! $summary['tahap_1_selesai']) {
                $validator->errors()->add('tahap', 'Tahap 2 tidak dapat dibuat karena Tahap 1 belum selesai.');
            }

            if (! $summary['interval_terpenuhi']) {
                $hari = $summary['interval_hari_sejak_tahap_1'] ?? 0;
                $tanggalMin = $summary['tanggal_minimum_tahap_2'] ?? '-';
                $validator->errors()->add('tahap', "Interval minimal 60 hari belum terpenuhi (baru {$hari} hari). Tahap 2 dapat dimulai setelah {$tanggalMin}.");
            }
        }
    }

    /**
     * Validasi konfirmasi jika melebihi rencana tahap.
     */
    private function validateOverPlanConfirmation($validator): void
    {
        $ureaRealisasi = (float) $this->input('urea_realisasi_kg', 0);
        $kclRealisasi = (float) $this->input('kcl_realisasi_kg', 0);
        $ureaRencana = (float) $this->input('urea_rencana_kg', 0);
        $kclRencana = (float) $this->input('kcl_rencana_kg', 0);

        $melebihiRencana = ($ureaRealisasi > $ureaRencana && $ureaRencana > 0)
            || ($kclRealisasi > $kclRencana && $kclRencana > 0);

        if ($melebihiRencana && ! $this->boolean('confirmed_over_plan')) {
            $validator->errors()->add('confirmed_over_plan', 'Realisasi melebihi rencana tahap. Centang konfirmasi dan sertakan catatan alasan.');
        }

        if ($melebihiRencana && ! $this->filled('catatan_pelaksana')) {
            $validator->errors()->add('catatan_pelaksana', 'Catatan wajib diisi jika realisasi melebihi rencana tahap.');
        }
    }

    /**
     * Validasi override kebutuhan tahunan.
     */
    private function validateAnnualLimit($validator): void
    {
        $rekomendasiId = $this->input('rekomendasi_rbs_id');
        if (! $rekomendasiId) {
            return;
        }

        $rekomendasi = RekomendasiRbs::find($rekomendasiId);
        if (! $rekomendasi) {
            return;
        }

        $totalTahunanUrea = (float) ($rekomendasi->urea_total_estimasi_tahunan ?? 0);
        $totalTahunanKcl = (float) ($rekomendasi->kcl_total_estimasi_tahunan ?? 0);

        if ($totalTahunanUrea <= 0 && $totalTahunanKcl <= 0) {
            return;
        }

        $blok = $rekomendasi->blokLahan;
        $realizationService = app(FertilizationRealizationService::class);
        $summary = $realizationService->getRealizationSummary($blok, $rekomendasiId);

        // Total yang akan tercatat setelah realisasi ini
        $totalUreaSetelah = $summary['total_urea_realisasi'] + (float) $this->input('urea_realisasi_kg', 0);
        $totalKclSetelah = $summary['total_kcl_realisasi'] + (float) $this->input('kcl_realisasi_kg', 0);

        $melebihiTahunan = ($totalUreaSetelah > $totalTahunanUrea && $totalTahunanUrea > 0)
            || ($totalKclSetelah > $totalTahunanKcl && $totalTahunanKcl > 0);

        if ($melebihiTahunan) {
            if (! $this->boolean('override_annual_limit')) {
                $selisihUrea = round(max(0, $totalUreaSetelah - $totalTahunanUrea), 2);
                $selisihKcl = round(max(0, $totalKclSetelah - $totalTahunanKcl), 2);
                $validator->errors()->add('override_annual_limit', "Total realisasi akan melebihi kebutuhan tahunan (Urea +{$selisihUrea} kg, KCl +{$selisihKcl} kg). Centang override dan isi alasan untuk melanjutkan.");
            }

            if ($this->boolean('override_annual_limit') && ! $this->filled('override_reason')) {
                $validator->errors()->add('override_reason', 'Alasan override wajib diisi jika melebihi kebutuhan tahunan.');
            }
        }
    }

    public function messages(): array
    {
        return [
            'rekomendasi_rbs_id.required' => 'Rekomendasi RBS wajib dipilih.',
            'rekomendasi_rbs_id.exists' => 'Rekomendasi RBS tidak valid.',
            'tahap.required' => 'Tahap pemupukan wajib dipilih.',
            'tahap.in' => 'Tahap hanya boleh 1 atau 2.',
            'tanggal_realisasi.required' => 'Tanggal realisasi wajib diisi.',
            'tanggal_realisasi.before_or_equal' => 'Tanggal realisasi tidak boleh di masa depan.',
            'urea_realisasi_kg.min' => 'Jumlah Urea tidak boleh negatif.',
            'kcl_realisasi_kg.min' => 'Jumlah KCl tidak boleh negatif.',
            'status_realisasi.in' => 'Status realisasi hanya boleh SELESAI, SEBAGIAN, atau BATAL.',
            'override_reason.required_if' => 'Alasan override wajib diisi.',
        ];
    }
}
