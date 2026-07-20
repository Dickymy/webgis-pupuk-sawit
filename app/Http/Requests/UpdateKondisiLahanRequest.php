<?php

namespace App\Http\Requests;

use App\Models\BlokLahan;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKondisiLahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'blok_lahan_id' => ['required', 'exists:blok_lahans,id'],
            'tanggal_observasi' => ['required', 'date'],
            'tanggal_pemupukan_terakhir' => ['nullable', 'date'],
            'ph_tanah' => ['nullable', 'numeric', 'min:3', 'max:8'],
            'metode_pengukuran_ph' => ['nullable', 'in:kertas_lakmus,ph_meter,estimasi,laboratorium'],
            'kelembaban_tanah' => ['nullable', 'string'],
            'curah_hujan_kategori' => ['nullable', 'string'],
            'curah_hujan_mm_bulanan' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'periode_curah_hujan' => ['nullable', 'string', 'max:50'],
            'sumber_curah_hujan' => ['nullable', 'in:manual,open-meteo,alat_ukur,lainnya'],
            'musim_saat_ini' => ['nullable', 'string'],
            'warna_daun' => ['nullable', 'string'],
            'kondisi_pelepah' => ['nullable', 'string'],
            'gejala_defisiensi' => ['nullable', 'array'],
            'gejala_defisiensi.*' => ['string'],
            'kondisi_tandan' => ['nullable', 'string'],
            'kondisi_drainase' => ['nullable', 'string'],
            'ada_gulma_dominan' => ['nullable', 'boolean'],
            'ada_serangan_hama' => ['nullable', 'boolean'],
            'catatan_observasi' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'blok_lahan_id.required' => 'Blok lahan wajib dipilih.',
            'tanggal_observasi.required' => 'Tanggal observasi wajib diisi.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDates($validator);
        });
    }

    private function validateDates($validator): void
    {
        $tanggalObservasi = $this->input('tanggal_observasi');
        $tanggalPemupukan = $this->input('tanggal_pemupukan_terakhir');
        $blokLahanId = $this->input('blok_lahan_id');

        if ($tanggalObservasi && $tanggalPemupukan) {
            if (strtotime($tanggalPemupukan) > strtotime($tanggalObservasi)) {
                $validator->errors()->add(
                    'tanggal_pemupukan_terakhir',
                    'Tanggal pemupukan terakhir tidak boleh lebih baru dari tanggal observasi.'
                );
            }
        }

        if ($tanggalObservasi && $blokLahanId) {
            $blok = BlokLahan::find($blokLahanId);
            if ($blok && $blok->tahun_tanam) {
                $tahunObservasi = (int) date('Y', strtotime($tanggalObservasi));
                if ($tahunObservasi < $blok->tahun_tanam) {
                    $validator->errors()->add(
                        'tanggal_observasi',
                        "Tanggal observasi tidak boleh sebelum tahun tanam blok ({$blok->tahun_tanam})."
                    );
                }
            }
        }
    }
}
