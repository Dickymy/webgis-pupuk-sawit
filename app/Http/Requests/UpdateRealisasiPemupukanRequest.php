<?php

namespace App\Http\Requests;

use App\Models\RealisasiPemupukan;
use App\Services\FertilizationRealizationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi update realisasi pemupukan.
 */
class UpdateRealisasiPemupukanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
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
     * Validasi tambahan.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $urea = (float) $this->input('urea_realisasi_kg', 0);
            $kcl = (float) $this->input('kcl_realisasi_kg', 0);
            if ($urea <= 0 && $kcl <= 0) {
                $validator->errors()->add('urea_realisasi_kg', 'Minimal salah satu pupuk (Urea atau KCl) harus lebih dari 0. Gunakan aksi pembatalan jika realisasi dibatalkan.');
            }

            $this->validateOverPlanConfirmation($validator);
            $this->validateAnnualLimit($validator);
        });
    }

    private function validateOverPlanConfirmation($validator): void
    {
        $realisasi = $this->route('realisasiPemupukan');
        $ureaRealisasi = (float) $this->input('urea_realisasi_kg', 0);
        $kclRealisasi = (float) $this->input('kcl_realisasi_kg', 0);
        $ureaRencana = (float) $realisasi->urea_rencana_kg;
        $kclRencana = (float) $realisasi->kcl_rencana_kg;

        $melebihiRencana = ($ureaRealisasi > $ureaRencana && $ureaRencana > 0)
            || ($kclRealisasi > $kclRencana && $kclRencana > 0);

        if ($melebihiRencana && ! $this->boolean('confirmed_over_plan')) {
            $validator->errors()->add('confirmed_over_plan', 'Realisasi melebihi rencana tahap. Centang konfirmasi dan sertakan catatan alasan.');
        }

        if ($melebihiRencana && ! $this->filled('catatan_pelaksana')) {
            $validator->errors()->add('catatan_pelaksana', 'Catatan wajib diisi jika realisasi melebihi rencana tahap.');
        }
    }

    private function validateAnnualLimit($validator): void
    {
        $realisasi = $this->route('realisasiPemupukan');
        $rekomendasi = $realisasi->rekomendasiRbs;
        if (! $rekomendasi) {
            return;
        }

        $totalTahunanUrea = (float) ($rekomendasi->urea_total_estimasi_tahunan ?? 0);
        $totalTahunanKcl = (float) ($rekomendasi->kcl_total_estimasi_tahunan ?? 0);

        if ($totalTahunanUrea <= 0 && $totalTahunanKcl <= 0) {
            return;
        }

        $blok = $realisasi->blokLahan;
        $realizationService = app(FertilizationRealizationService::class);
        $summary = $rekomendasi->programPemupukan
            ? $realizationService->getRealizationSummaryForProgram($rekomendasi->programPemupukan)
            : $realizationService->getRealizationSummary($blok, $rekomendasi->id);

        // Kurangi realisasi saat ini (karena akan diupdate)
        $totalUreaSebelum = $summary['total_urea_realisasi'] - (float) $realisasi->urea_realisasi_kg;
        $totalKclSebelum = $summary['total_kcl_realisasi'] - (float) $realisasi->kcl_realisasi_kg;

        $totalUreaSetelah = $totalUreaSebelum + (float) $this->input('urea_realisasi_kg', 0);
        $totalKclSetelah = $totalKclSebelum + (float) $this->input('kcl_realisasi_kg', 0);

        $melebihiTahunan = ($totalUreaSetelah > $totalTahunanUrea && $totalTahunanUrea > 0)
            || ($totalKclSetelah > $totalTahunanKcl && $totalTahunanKcl > 0);

        if ($melebihiTahunan && ! $this->boolean('override_annual_limit')) {
            $validator->errors()->add('override_annual_limit', 'Total realisasi akan melebihi kebutuhan tahunan. Centang override dan isi alasan.');
        }

        if ($melebihiTahunan && $this->boolean('override_annual_limit') && ! $this->filled('override_reason')) {
            $validator->errors()->add('override_reason', 'Alasan override wajib diisi.');
        }
    }

    public function messages(): array
    {
        return [
            'tanggal_realisasi.required' => 'Tanggal realisasi wajib diisi.',
            'tanggal_realisasi.before_or_equal' => 'Tanggal realisasi tidak boleh di masa depan.',
            'status_realisasi.in' => 'Status realisasi tidak valid.',
            'override_reason.required_if' => 'Alasan override wajib diisi.',
        ];
    }
}
