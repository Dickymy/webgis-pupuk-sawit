<?php

namespace App\Http\Requests;

use App\Models\RealisasiPemupukan;
use App\Models\RekomendasiRbs;
use App\Services\RealisasiEligibilityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pencatatan realisasi pemupukan.
 *
 * Pahan v2.7:
 * - Form hanya mengirim field yang boleh diisi user
 * - Server menghitung tahap, tahun_program, rencana (tidak dipercaya dari browser)
 * - Validasi status SELESAI dilakukan di controller terhadap jumlah kumulatif
 * - Override tetap dikirim untuk kasus melebihi rencana/tahunan
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
            'tanggal_realisasi' => ['required', 'date', 'before_or_equal:today'],
            'urea_realisasi_kg' => ['required', 'numeric', 'min:0'],
            'kcl_realisasi_kg' => ['required', 'numeric', 'min:0'],
            'status_realisasi' => ['required', 'string', Rule::in([
                RealisasiPemupukan::STATUS_SELESAI,
                RealisasiPemupukan::STATUS_SEBAGIAN,
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
            $this->validateOverPlanAndAnnualLimit($validator);
        });
    }

    /**
     * Validasi over-plan dan annual limit server-side.
     */
    private function validateOverPlanAndAnnualLimit($validator): void
    {
        $rekomendasiId = $this->input('rekomendasi_rbs_id');
        if (! $rekomendasiId) {
            return;
        }

        $rekomendasi = RekomendasiRbs::find($rekomendasiId);
        if (! $rekomendasi) {
            return;
        }

        // Hitung rencana resmi server-side
        $eligibilityService = app(RealisasiEligibilityService::class);
        $eligibility = $eligibilityService->evaluate($rekomendasi);

        $ureaRencana = $eligibility['urea_rencana_kg'];
        $kclRencana = $eligibility['kcl_rencana_kg'];
        $ureaRealisasi = (float) $this->input('urea_realisasi_kg', 0);
        $kclRealisasi = (float) $this->input('kcl_realisasi_kg', 0);

        // Cek over-plan
        $melebihiRencana = ($ureaRealisasi > $ureaRencana && $ureaRencana > 0)
            || ($kclRealisasi > $kclRencana && $kclRencana > 0);

        if ($melebihiRencana && ! $this->boolean('confirmed_over_plan')) {
            $validator->errors()->add('confirmed_over_plan', 'Realisasi melebihi rencana tahap. Centang konfirmasi dan sertakan catatan alasan.');
        }

        if ($melebihiRencana && ! $this->filled('catatan_pelaksana')) {
            $validator->errors()->add('catatan_pelaksana', 'Catatan wajib diisi jika realisasi melebihi rencana tahap.');
        }

        // Cek override tahunan
        $totalTahunanUrea = (float) ($rekomendasi->urea_total_estimasi_tahunan ?? 0);
        $totalTahunanKcl = (float) ($rekomendasi->kcl_total_estimasi_tahunan ?? 0);

        if ($totalTahunanUrea > 0 || $totalTahunanKcl > 0) {
            $summary = $eligibility['realization_summary'];
            $totalUreaSetelah = ($summary['total_urea_realisasi'] ?? 0) + $ureaRealisasi;
            $totalKclSetelah = ($summary['total_kcl_realisasi'] ?? 0) + $kclRealisasi;

            $melebihiTahunan = ($totalUreaSetelah > $totalTahunanUrea && $totalTahunanUrea > 0)
                || ($totalKclSetelah > $totalTahunanKcl && $totalTahunanKcl > 0);

            if ($melebihiTahunan && ! $this->boolean('override_annual_limit')) {
                $validator->errors()->add('override_annual_limit', 'Total realisasi akan melebihi kebutuhan tahunan. Centang override dan isi alasan.');
            }

            if ($melebihiTahunan && $this->boolean('override_annual_limit') && ! $this->filled('override_reason')) {
                $validator->errors()->add('override_reason', 'Alasan override wajib diisi jika melebihi kebutuhan tahunan.');
            }
        }
    }

    public function messages(): array
    {
        return [
            'rekomendasi_rbs_id.required' => 'Rekomendasi RBS wajib dipilih.',
            'rekomendasi_rbs_id.exists' => 'Rekomendasi RBS tidak valid.',
            'tanggal_realisasi.required' => 'Tanggal realisasi wajib diisi.',
            'tanggal_realisasi.before_or_equal' => 'Tanggal realisasi tidak boleh di masa depan.',
            'urea_realisasi_kg.min' => 'Jumlah Urea tidak boleh negatif.',
            'kcl_realisasi_kg.min' => 'Jumlah KCl tidak boleh negatif.',
            'status_realisasi.in' => 'Status realisasi hanya boleh SELESAI atau SEBAGIAN.',
            'override_reason.required_if' => 'Alasan override wajib diisi.',
        ];
    }
}
