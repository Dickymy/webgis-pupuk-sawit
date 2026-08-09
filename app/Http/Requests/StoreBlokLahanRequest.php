<?php

namespace App\Http\Requests;

use App\Enums\PlantPhase;
use Illuminate\Foundation\Http\FormRequest;

class StoreBlokLahanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'anggota_id' => ['required', 'exists:anggotas,id'],
            'nama_blok' => ['required', 'string', 'max:100'],
            'luas_ha' => ['required', 'numeric', 'min:0.01'],
            'sph' => ['required', 'integer', 'min:1'],
            'koordinat_geojson' => ['required', 'string'],
            'tahun_tanam' => ['required', 'integer', 'min:1990', 'max:'.now()->year],
            'jenis_tanah' => ['required', 'in:Tanah Lempung,Tanah Lempung Berpasir,Tanah Berpasir,Tanah Liat,Tanah Gambut,Tanah Aluvial,Tanah Podsolik Merah Kuning (PMK),Tanah Laterit,Tanah Berbatu,Lainnya'],
            'topografi' => ['required', 'in:Datar - Landai (< 12°),Bergelombang - Miring (12° - 23°),Curam - Berbukit (> 23°)'],
            'fase_tanaman' => ['nullable', 'in:TBM,TM'],
            'jumlah_pohon' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'anggota_id.required' => 'Pemilik lahan wajib dipilih.',
            'nama_blok.required' => 'Nama blok wajib diisi.',
            'luas_ha.required' => 'Luas lahan wajib diisi.',
            'sph.required' => 'SPH wajib diisi.',
            'koordinat_geojson.required' => 'Koordinat GeoJSON wajib diisi.',
            'tahun_tanam.required' => 'Tahun tanam wajib diisi.',
            'jenis_tanah.required' => 'Jenis tanah wajib dipilih.',
            'topografi.required' => 'Topografi wajib dipilih.',
        ];
    }

    /**
     * Validasi tambahan: konsistensi fase tanaman dengan umur.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $this->validatePhaseConsistency($validator);
            $this->validateGeoJson($validator);
        });
    }

    private function validatePhaseConsistency($validator): void
    {
        $tahunTanam = $this->input('tahun_tanam');
        $fase = $this->input('fase_tanaman');

        if (! $tahunTanam) {
            return;
        }

        $umur = now()->year - (int) $tahunTanam;

        // Umur < 3: hanya TBM valid
        if ($umur < 3 && $fase === PlantPhase::MENGHASILKAN->value) {
            $validator->errors()->add(
                'fase_tanaman',
                "Tanaman berumur {$umur} tahun tidak dapat dikategorikan sebagai Tanaman Menghasilkan. Umur kurang dari 3 tahun hanya valid untuk Tanaman Belum Menghasilkan."
            );
        }

        // Umur > 3: hanya TM valid
        if ($umur > 3 && $fase === PlantPhase::BELUM_MENGHASILKAN->value) {
            $validator->errors()->add(
                'fase_tanaman',
                "Tanaman berumur {$umur} tahun tidak dapat dikategorikan sebagai Tanaman Belum Menghasilkan. Umur lebih dari 3 tahun hanya valid untuk Tanaman Menghasilkan."
            );
        }

        // Umur = 3: keduanya valid, tapi wajib dipilih
        if ($umur === 3 && $fase === null) {
            $validator->errors()->add(
                'fase_tanaman',
                'Umur tanaman tepat 3 tahun dapat berada pada fase Tanaman Belum Menghasilkan atau Tanaman Menghasilkan. Pilih berdasarkan kondisi aktual di lapangan.'
            );
        }
    }

    private function validateGeoJson($validator): void
    {
        $geojson = $this->input('koordinat_geojson');
        if (! $geojson) {
            return;
        }

        $decoded = json_decode($geojson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $validator->errors()->add('koordinat_geojson', 'Format GeoJSON tidak valid.');

            return;
        }

        $type = $decoded['type'] ?? null;
        $isValidGeometry = in_array($type, ['Polygon', 'MultiPolygon', 'Feature', 'FeatureCollection']);
        if (! $isValidGeometry) {
            $validator->errors()->add('koordinat_geojson', 'GeoJSON harus berupa Polygon, MultiPolygon, Feature, atau FeatureCollection.');

            return;
        }

        if ($type === 'Polygon' && (empty($decoded['coordinates']) || empty($decoded['coordinates'][0]) || count($decoded['coordinates'][0]) < 4)) {
            $validator->errors()->add('koordinat_geojson', 'Polygon harus memiliki minimal 4 titik koordinat.');
        }
    }
}
