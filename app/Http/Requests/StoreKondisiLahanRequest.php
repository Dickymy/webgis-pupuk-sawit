<?php

namespace App\Http\Requests;

use App\Models\BlokLahan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreKondisiLahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('metode_data_hujan')) {
            $mode = $this->filled('curah_hujan_mm_bulanan')
                ? 'data_angka'
                : ($this->filled('curah_hujan_kategori') ? 'perkiraan' : 'tidak_tersedia');
            $this->merge(['metode_data_hujan' => $mode]);
        }
    }

    public function rules(): array
    {
        $leafValues = array_merge(
            config('observation.leaf_conditions', []),
            array_keys(config('observation.unmatched_leaf_values', []))
        );

        return [
            'anggota_id' => ['nullable', 'exists:anggotas,id'],
            'blok_lahan_id' => ['required', 'exists:blok_lahans,id'],
            'tanggal_observasi' => ['required', 'date', 'before_or_equal:today'],
            'tanggal_pemupukan_terakhir' => ['nullable', 'date', 'before_or_equal:today'],
            'kelembaban_tanah' => ['nullable', Rule::in(['Sangat Kering', 'Kering', 'Normal', 'Lembab', 'Sangat Lembab'])],
            'metode_data_hujan' => ['nullable', Rule::in(['data_angka', 'perkiraan', 'tidak_tersedia'])],
            'mode_data_hujan_dikonfirmasi' => ['nullable', 'boolean'],
            'curah_hujan_kategori' => ['nullable', Rule::in(['Sangat Rendah', 'Rendah', 'Normal', 'Tinggi', 'Sangat Tinggi'])],
            'curah_hujan_mm_bulanan' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'periode_curah_hujan' => ['nullable', 'string', 'max:50'],
            'sumber_curah_hujan' => ['nullable', 'in:manual,open-meteo,alat_ukur,lainnya'],
            'musim_saat_ini' => ['nullable', Rule::in(['Musim Hujan', 'Musim Kemarau', 'Peralihan'])],
            'warna_daun' => ['required', Rule::in($leafValues)],
            'gejala_defisiensi' => ['nullable', 'array'],
            'gejala_defisiensi.*' => ['string'],
            'kondisi_drainase' => ['nullable', Rule::in(['Baik', 'Cukup', 'Buruk — Tergenang'])],
            'ada_gulma_dominan' => ['nullable', 'boolean'],
            'ada_serangan_hama' => ['nullable', 'boolean'],
            'catatan_observasi' => ['nullable', 'string', 'max:1000'],
            'foto_observasi' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'blok_lahan_id.required' => 'Blok lahan wajib dipilih.',
            'tanggal_observasi.required' => 'Tanggal observasi wajib diisi.',
            'tanggal_observasi.before_or_equal' => 'Tanggal observasi tidak boleh di masa depan.',
            'tanggal_pemupukan_terakhir.before_or_equal' => 'Tanggal pemupukan terakhir tidak boleh di masa depan.',
            'warna_daun.required' => 'Pilih hasil pemeriksaan kondisi daun.',
            'curah_hujan_mm_bulanan.numeric' => 'Curah hujan harus berupa angka.',
            'curah_hujan_mm_bulanan.min' => 'Curah hujan tidak boleh negatif.',
            'foto_observasi.image' => 'Foto observasi harus berupa gambar.',
            'foto_observasi.mimes' => 'Foto harus berformat JPG, PNG, atau WebP.',
            'foto_observasi.max' => 'Ukuran foto maksimal 4 MB.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validateDates($validator);
            $this->validateFieldObservation($validator);
            $this->validateOwnerBlock($validator);
        });
    }

    private function validateFieldObservation($validator): void
    {
        if ($this->boolean('mode_data_hujan_dikonfirmasi')) {
            if ($this->input('metode_data_hujan') === 'data_angka') {
                foreach ([
                    'curah_hujan_mm_bulanan' => 'Masukkan jumlah curah hujan dalam mm/bulan.',
                    'periode_curah_hujan' => 'Tuliskan periode data curah hujan.',
                    'sumber_curah_hujan' => 'Pilih sumber data curah hujan.',
                ] as $field => $message) {
                    if (! $this->filled($field)) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            if ($this->input('metode_data_hujan') === 'perkiraan' && ! $this->filled('curah_hujan_kategori')) {
                $validator->errors()->add('curah_hujan_kategori', 'Pilih perkiraan kondisi curah hujan.');
            }
        }

        if ($this->input('warna_daun') === '__gejala_lain' && ! $this->filled('catatan_observasi')) {
            $validator->errors()->add('catatan_observasi', 'Jelaskan gejala lain yang terlihat pada catatan lapangan.');
        }
    }

    private function validateOwnerBlock($validator): void
    {
        if (! $this->filled('anggota_id') || ! $this->filled('blok_lahan_id')) {
            return;
        }

        $blok = BlokLahan::find($this->input('blok_lahan_id'));
        if ($blok && (int) $blok->anggota_id !== (int) $this->input('anggota_id')) {
            $validator->errors()->add('blok_lahan_id', 'Blok yang dipilih tidak sesuai dengan anggota.');
        }
    }

    private function validateDates($validator): void
    {
        $tanggalObservasi = $this->input('tanggal_observasi');
        $tanggalPemupukan = $this->input('tanggal_pemupukan_terakhir');
        $blokLahanId = $this->input('blok_lahan_id');

        if ($tanggalObservasi && $tanggalPemupukan && strtotime($tanggalPemupukan) > strtotime($tanggalObservasi)) {
            $validator->errors()->add(
                'tanggal_pemupukan_terakhir',
                'Tanggal pemupukan terakhir tidak boleh lebih baru dari tanggal observasi.'
            );
        }

        if ($tanggalObservasi && $blokLahanId) {
            $blok = BlokLahan::find($blokLahanId);
            if ($blok && $blok->tahun_tanam && (int) date('Y', strtotime($tanggalObservasi)) < $blok->tahun_tanam) {
                $validator->errors()->add(
                    'tanggal_observasi',
                    "Tanggal observasi tidak boleh sebelum tahun tanam blok ({$blok->tahun_tanam})."
                );
            }
        }
    }
}
