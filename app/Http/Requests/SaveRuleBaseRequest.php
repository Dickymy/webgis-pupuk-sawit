<?php

namespace App\Http\Requests;

use App\Models\RuleBaseLanjutan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveRuleBaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    protected function prepareForValidation(): void
    {
        $rule = $this->route('rule');
        $jenisRule = $rule instanceof RuleBaseLanjutan
            ? $rule->jenis_rule
            : $this->input('jenis_rule');

        $nullableFields = [
            'kondisi_warna_daun',
            'kondisi_curah_hujan_min_mm',
            'kondisi_curah_hujan_max_mm',
            'jenis_pupuk_utama',
            'sumber_penulis',
            'sumber_tahun',
            'sumber_halaman',
            'sumber_tabel',
            'catatan_validasi',
        ];

        $normalized = ['jenis_rule' => $jenisRule];
        foreach ($nullableFields as $field) {
            $value = $this->input($field);
            $normalized[$field] = is_string($value) && trim($value) === '' ? null : $value;
        }

        $this->merge($normalized);
    }

    public function rules(): array
    {
        return [
            'jenis_rule' => ['required', Rule::in(['DIAGNOSIS_VISUAL', 'PEMBATAS_APLIKASI'])],
            'kondisi_warna_daun' => [
                'nullable',
                'string',
                'max:100',
                Rule::in(config('observation.diagnostic_leaf_conditions')),
            ],
            'kondisi_curah_hujan_min_mm' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'kondisi_curah_hujan_max_mm' => ['nullable', 'numeric', 'min:0', 'max:2000'],
            'indikasi_masalah' => ['required', 'string', 'max:255'],
            'jenis_pupuk_utama' => [
                'nullable',
                Rule::in(['Urea', 'KCl', 'Tidak ditentukan otomatis']),
            ],
            'saran_tindakan' => ['required', 'string', 'max:1500'],
            'status_kebutuhan' => ['required', Rule::in(['Segera', 'Normal', 'Tunda'])],
            'tingkat_keparahan' => ['required', Rule::in(['RINGAN', 'SEDANG', 'BERAT', 'NORMAL'])],
            'prioritas' => ['required', 'integer', 'min:1', 'max:10'],
            'tingkat_bukti' => ['required', Rule::in(['BUKU', 'JURNAL', 'AHLI', 'ADAPTASI_PENELITI'])],
            'sumber_judul' => ['required', 'string', 'max:255'],
            'sumber_penulis' => ['nullable', 'string', 'max:100'],
            'sumber_tahun' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'sumber_halaman' => ['nullable', 'string', 'max:50'],
            'sumber_tabel' => ['nullable', 'string', 'max:50'],
            'catatan_validasi' => ['nullable', 'string', 'max:1500'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $jenisRule = $this->input('jenis_rule');
                $min = $this->input('kondisi_curah_hujan_min_mm');
                $max = $this->input('kondisi_curah_hujan_max_mm');

                if ($jenisRule === 'DIAGNOSIS_VISUAL') {
                    if (! $this->filled('kondisi_warna_daun')) {
                        $validator->errors()->add('kondisi_warna_daun', 'Pilih kondisi daun yang akan diperiksa.');
                    }
                    if (! $this->filled('jenis_pupuk_utama')) {
                        $validator->errors()->add('jenis_pupuk_utama', 'Pilih hubungan rule dengan pupuk.');
                    }
                    if ($this->input('status_kebutuhan') === 'Tunda') {
                        $validator->errors()->add('status_kebutuhan', 'Rule kondisi daun tidak menggunakan status tunda.');
                    }
                    if ($this->input('tingkat_keparahan') === 'NORMAL') {
                        $validator->errors()->add('tingkat_keparahan', 'Pilih tingkat perhatian untuk gejala daun.');
                    }
                }

                if ($jenisRule === 'PEMBATAS_APLIKASI') {
                    if ($min === null && $max === null) {
                        $validator->errors()->add('kondisi_curah_hujan_min_mm', 'Isi batas minimum atau maksimum curah hujan.');
                    }
                    if ($min !== null && $max !== null && (float) $min > (float) $max) {
                        $validator->errors()->add('kondisi_curah_hujan_max_mm', 'Batas maksimum harus lebih besar atau sama dengan batas minimum.');
                    }
                    if (! in_array($this->input('status_kebutuhan'), ['Normal', 'Tunda'], true)) {
                        $validator->errors()->add('status_kebutuhan', 'Rule waktu hanya menggunakan status dapat dijadwalkan atau ditunda.');
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'sumber_tahun.max' => 'Tahun sumber tidak boleh melewati tahun berjalan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'indikasi_masalah' => 'Kesimpulan rule',
            'saran_tindakan' => 'Saran tindakan',
            'sumber_judul' => 'Sumber acuan',
            'sumber_penulis' => 'Penulis sumber',
            'sumber_tahun' => 'Tahun sumber',
            'sumber_halaman' => 'Halaman sumber',
            'catatan_validasi' => 'Hubungan sumber dengan rule',
        ];
    }
}
