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

    public function info()
    {
        return view('rule_base.info');
    }

    public function create()
    {
        return view('rule_base.create');
    }

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
        $rule = RuleBaseLanjutan::findOrFail($id);
        $rule->delete();

        return redirect()->route('rule-base.index')->with('success', 'Rule berhasil dihapus.');
    }

    /**
     * Validasi rule RBS.
     */
    private function validateRule(Request $request): array
    {
        $validated = $request->validate([
            // Kondisi (IF) — semua nullable
            'kondisi_warna_daun' => ['nullable', 'string', 'max:100'],
            'kondisi_ph_min' => ['nullable', 'numeric', 'min:3', 'max:8'],
            'kondisi_ph_max' => ['nullable', 'numeric', 'min:3', 'max:8'],
            'kondisi_kelembaban' => ['nullable', 'string', 'max:50'],
            'kondisi_curah_hujan_kategori' => ['nullable', 'string', 'max:50'],
            'kondisi_musim' => ['nullable', 'string', 'max:50'],
            'kondisi_drainase' => ['nullable', 'string', 'max:50'],
            'kondisi_defisiensi' => ['nullable', 'string', 'max:50'],
            'kondisi_kategori_umur' => ['nullable', 'string', 'max:50'],
            'kondisi_pelepah' => ['nullable', 'string', 'max:100'],
            'kondisi_tandan' => ['nullable', 'string', 'max:100'],
            'ada_serangan_hama' => ['nullable'],
            'ada_gulma_dominan' => ['nullable'],
            // Output (THEN)
            'indikasi_masalah' => ['required', 'string', 'max:255'],
            'jenis_pupuk_utama' => ['required', 'string', 'max:100'],
            'jenis_pupuk_pendukung' => ['nullable', 'string', 'max:100'],
            'dosis_anjuran' => ['required', 'string', 'max:150'],
            'metode_aplikasi' => ['nullable', 'string', 'max:255'],
            'waktu_aplikasi' => ['nullable', 'string', 'max:150'],
            'saran_tindakan' => ['required', 'string', 'max:2000'],
            'status_kebutuhan' => ['required', 'in:Darurat,Segera,Normal,Tunda'],
            'prioritas' => ['required', 'integer', 'min:1', 'max:10'],
            'aktif' => ['nullable'],
            'keterangan_rule' => ['nullable', 'string', 'max:1000'],
        ], [
            'indikasi_masalah.required' => 'Indikasi masalah wajib diisi.',
            'jenis_pupuk_utama.required' => 'Jenis pupuk utama wajib diisi.',
            'dosis_anjuran.required' => 'Dosis anjuran wajib diisi.',
            'saran_tindakan.required' => 'Saran tindakan wajib diisi.',
            'status_kebutuhan.required' => 'Status kebutuhan wajib dipilih.',
            'prioritas.required' => 'Prioritas wajib diisi.',
        ]);

        // Handle boolean/nullable checkbox fields
        $validated['aktif'] = $request->boolean('aktif');
        $validated['ada_serangan_hama'] = $request->has('ada_serangan_hama')
            ? ($request->input('ada_serangan_hama') === 'null' ? null : $request->boolean('ada_serangan_hama'))
            : null;
        $validated['ada_gulma_dominan'] = $request->has('ada_gulma_dominan')
            ? ($request->input('ada_gulma_dominan') === 'null' ? null : $request->boolean('ada_gulma_dominan'))
            : null;

        // Bersihkan string kosong jadi null
        foreach ($validated as $key => $value) {
            if (is_string($value) && trim($value) === '') {
                $validated[$key] = null;
            }
        }

        return $validated;
    }
}
