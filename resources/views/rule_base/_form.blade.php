@php
    $isEdit = isset($rule);
    $selectedType = old('jenis_rule', $rule->jenis_rule ?? 'DIAGNOSIS_VISUAL');
@endphp

@if($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200" role="alert">
        <p class="font-semibold">Periksa kembali data berikut:</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-800">
    <div class="flex items-start gap-3">
        <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">1</span>
        <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Kondisi yang diperiksa</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Pilih satu jenis rule agar kondisi mudah ditelusuri dan tidak bertentangan.</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div>
            <label for="jenis_rule" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Jenis rule <span class="text-red-500">*</span></label>
            @if($isEdit)
                <input type="hidden" name="jenis_rule" value="{{ $rule->jenis_rule }}">
                <div class="flex min-h-11 items-center rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700 dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-200">
                    {{ $rule->jenis_rule === 'DIAGNOSIS_VISUAL' ? 'Diagnosis Visual' : 'Waktu pemupukan' }}
                </div>
                <p class="mt-1 text-xs text-slate-400">Jenis rule tidak diubah agar kode dan riwayat tetap konsisten.</p>
            @else
                <select id="jenis_rule" name="jenis_rule" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                    <option value="DIAGNOSIS_VISUAL" @selected($selectedType === 'DIAGNOSIS_VISUAL')>Diagnosis Visual</option>
                    <option value="PEMBATAS_APLIKASI" @selected($selectedType === 'PEMBATAS_APLIKASI')>Waktu pemupukan</option>
                </select>
            @endif
        </div>

        <div data-rule-section="visual" class="lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="kondisi_warna_daun" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Kondisi daun <span class="text-red-500">*</span></label>
                    <select id="kondisi_warna_daun" name="kondisi_warna_daun" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <option value="">(Abaikan) - Tidak mengecek daun</option>
                        @foreach($leafOptions as $option)
                            <option value="{{ $option }}" @selected(old('kondisi_warna_daun', $rule->kondisi_warna_daun ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="kondisi_topografi" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Kondisi Topografi <span class="text-red-500">*</span></label>
                    <select id="kondisi_topografi" name="kondisi_topografi" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <option value="">(Abaikan) - Semua topografi</option>
                        @foreach(['Datar - Landai (< 12°)', 'Bergelombang - Miring (12° - 23°)', 'Curam - Berbukit (> 23°)'] as $option)
                            <option value="{{ $option }}" @selected(old('kondisi_topografi', $rule->kondisi_topografi ?? '') === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <p class="mt-2 text-xs text-slate-400">Pilih salah satu atau keduanya. Kosongkan jika tidak relevan dengan rule yang dibuat.</p>
        </div>

        <div data-rule-section="timing" class="hidden lg:col-span-2">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="kondisi_curah_hujan_min_mm" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Batas minimum</label>
                    <div class="relative">
                        <input id="kondisi_curah_hujan_min_mm" type="number" step="0.1" min="0" max="2000" name="kondisi_curah_hujan_min_mm" value="{{ old('kondisi_curah_hujan_min_mm', $rule->kondisi_curah_hujan_min_mm ?? '') }}" placeholder="Contoh: 100" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pr-24 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-400">mm/bulan</span>
                    </div>
                </div>
                <div>
                    <label for="kondisi_curah_hujan_max_mm" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Batas maksimum</label>
                    <div class="relative">
                        <input id="kondisi_curah_hujan_max_mm" type="number" step="0.1" min="0" max="2000" name="kondisi_curah_hujan_max_mm" value="{{ old('kondisi_curah_hujan_max_mm', $rule->kondisi_curah_hujan_max_mm ?? '') }}" placeholder="Contoh: 250" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 pr-24 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                        <span class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-xs text-slate-400">mm/bulan</span>
                    </div>
                </div>
            </div>
            <p class="mt-1 text-xs text-slate-400">Salah satu batas boleh dikosongkan. Sistem menolak rentang yang bertumpang tindih dengan rule aktif.</p>
        </div>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-800">
    <div class="flex items-start gap-3">
        <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded-full bg-emerald-100 text-sm font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">2</span>
        <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Hasil rule</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Tuliskan kesimpulan dan tindakan yang akan dilihat pengguna saat rule terpicu.</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-2">
        <div class="lg:col-span-2">
            <label for="indikasi_masalah" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Kesimpulan rule <span class="text-red-500">*</span></label>
            <input id="indikasi_masalah" type="text" name="indikasi_masalah" value="{{ old('indikasi_masalah', $rule->indikasi_masalah ?? '') }}" maxlength="255" placeholder="Contoh: Curah hujan mendukung waktu pemupukan" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
        </div>

        <div data-rule-section="visual">
            <label for="jenis_pupuk_utama" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Hubungan dengan pupuk <span class="text-red-500">*</span></label>
            <select id="jenis_pupuk_utama" name="jenis_pupuk_utama" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                <option value="">Pilih hubungan</option>
                @foreach(['Urea' => 'Berkaitan dengan Urea', 'KCl' => 'Berkaitan dengan KCl', 'Tidak ditentukan otomatis' => 'Tidak menentukan pupuk otomatis'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('jenis_pupuk_utama', $rule->jenis_pupuk_utama ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-400">Dosis tidak dapat diedit di sini; Urea dan KCl tetap dihitung dari acuan Iyung Pahan (2013).</p>
        </div>

        <div>
            <label for="status_kebutuhan" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Tindakan utama <span class="text-red-500">*</span></label>
            <select id="status_kebutuhan" name="status_kebutuhan" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                <option value="Segera" @selected(old('status_kebutuhan', $rule->status_kebutuhan ?? 'Segera') === 'Segera')>Perlu diperhatikan</option>
                <option value="Normal" @selected(old('status_kebutuhan', $rule->status_kebutuhan ?? '') === 'Normal')>Dapat dilanjutkan</option>
                <option value="Tunda" @selected(old('status_kebutuhan', $rule->status_kebutuhan ?? '') === 'Tunda')>Pemupukan ditunda</option>
            </select>
        </div>

        <div>
            <label for="tingkat_keparahan" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Tingkat perhatian <span class="text-red-500">*</span></label>
            <select id="tingkat_keparahan" name="tingkat_keparahan" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                <option value="RINGAN" @selected(old('tingkat_keparahan', $rule->tingkat_keparahan ?? 'RINGAN') === 'RINGAN')>Ringan</option>
                <option value="SEDANG" @selected(old('tingkat_keparahan', $rule->tingkat_keparahan ?? '') === 'SEDANG')>Sedang</option>
                <option value="BERAT" @selected(old('tingkat_keparahan', $rule->tingkat_keparahan ?? '') === 'BERAT')>Berat</option>
                <option value="NORMAL" @selected(old('tingkat_keparahan', $rule->tingkat_keparahan ?? '') === 'NORMAL')>Normal</option>
            </select>
        </div>

        <div>
            <label for="prioritas" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Urutan pemeriksaan <span class="text-red-500">*</span></label>
            <input id="prioritas" type="number" min="1" max="10" name="prioritas" value="{{ old('prioritas', $rule->prioritas ?? 5) }}" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            <p class="mt-1 text-xs text-slate-400">1 diperiksa lebih dahulu, 10 diperiksa paling akhir.</p>
        </div>

        <div class="lg:col-span-2">
            <label for="saran_tindakan" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Saran tindakan <span class="text-red-500">*</span></label>
            <textarea id="saran_tindakan" name="saran_tindakan" rows="3" maxlength="1500" placeholder="Jelaskan tindakan yang harus dilakukan pengguna" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-6 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">{{ old('saran_tindakan', $rule->saran_tindakan ?? '') }}</textarea>
        </div>
    </div>
</section>

<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 dark:border-slate-700 dark:bg-slate-800">
    <div class="flex items-start gap-3">
        <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded-full bg-amber-100 text-sm font-bold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">3</span>
        <div>
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Sumber acuan</h2>
            <p class="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">Cukup catat jenis dan nama sumber. Detail lainnya boleh dikosongkan.</p>
        </div>
    </div>

    <div class="mt-5 grid gap-5 lg:grid-cols-3">
        <div>
            <label for="tingkat_bukti" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Jenis sumber <span class="text-red-500">*</span></label>
            <select id="tingkat_bukti" name="tingkat_bukti" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
                @foreach(['BUKU' => 'Buku', 'JURNAL' => 'Jurnal', 'AHLI' => 'Keterangan ahli', 'ADAPTASI_PENELITI' => 'Adaptasi penelitian'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('tingkat_bukti', $rule->tingkat_bukti ?? 'JURNAL') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="lg:col-span-2">
            <label for="sumber_judul" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Sumber acuan <span class="text-red-500">*</span></label>
            <input id="sumber_judul" type="text" name="sumber_judul" value="{{ old('sumber_judul', $rule->sumber_judul ?? '') }}" maxlength="255" placeholder="Contoh: Iyung Pahan (2013)" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
        </div>
    </div>

    <details class="group mt-5 rounded-xl border border-slate-200 bg-slate-50/70 dark:border-slate-700 dark:bg-slate-900/40">
        <summary class="flex min-h-12 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <span>Detail tambahan <span class="font-normal text-slate-400">(opsional)</span></span>
            <svg class="h-4 w-4 text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </summary>
        <div class="grid gap-5 border-t border-slate-200 p-4 sm:grid-cols-2 lg:grid-cols-3 dark:border-slate-700">
            <div>
                <label for="sumber_penulis" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Penulis</label>
                <input id="sumber_penulis" type="text" name="sumber_penulis" value="{{ old('sumber_penulis', $rule->sumber_penulis ?? '') }}" maxlength="100" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
            <div>
                <label for="sumber_tahun" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Tahun</label>
                <input id="sumber_tahun" type="number" min="1900" max="{{ now()->year }}" name="sumber_tahun" value="{{ old('sumber_tahun', $rule->sumber_tahun ?? '') }}" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
            <div>
                <label for="sumber_halaman" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Halaman</label>
                <input id="sumber_halaman" type="text" name="sumber_halaman" value="{{ old('sumber_halaman', $rule->sumber_halaman ?? '') }}" maxlength="50" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
            <div>
                <label for="sumber_tabel" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Nomor tabel</label>
                <input id="sumber_tabel" type="text" name="sumber_tabel" value="{{ old('sumber_tabel', $rule->sumber_tabel ?? '') }}" maxlength="50" class="min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">
            </div>
            <div class="sm:col-span-2">
                <label for="catatan_validasi" class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">Catatan sumber</label>
                <textarea id="catatan_validasi" name="catatan_validasi" rows="2" maxlength="1500" placeholder="Catatan tambahan jika diperlukan" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm leading-6 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-900 dark:text-white">{{ old('catatan_validasi', $rule->catatan_validasi ?? '') }}</textarea>
            </div>
        </div>
    </details>
</section>
<div class="sticky bottom-20 z-20 flex flex-col-reverse gap-2 rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-lg backdrop-blur sm:bottom-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700 dark:bg-slate-800/95">
    <a href="{{ route('rule-base.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-600 dark:text-slate-200 dark:hover:bg-slate-700">Batal</a>
    <div class="text-xs leading-5 text-slate-500 dark:text-slate-400 sm:text-right">
        <p>Rule disimpan dalam keadaan belum digunakan.</p>
        <p>Periksa kembali kondisi, hasil, dan sumber sebelum memilih Gunakan.</p>
    </div>
    <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700">Simpan Rule</button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var typeInput = document.getElementById('jenis_rule') || document.querySelector('input[name="jenis_rule"]');
    var visualSections = document.querySelectorAll('[data-rule-section="visual"]');
    var timingSections = document.querySelectorAll('[data-rule-section="timing"]');
    var statusSelect = document.getElementById('status_kebutuhan');
    var severitySelect = document.getElementById('tingkat_keparahan');

    function updateRuleFields() {
        var isVisual = !typeInput || typeInput.value === 'DIAGNOSIS_VISUAL';
        visualSections.forEach(function (section) { section.classList.toggle('hidden', !isVisual); });
        timingSections.forEach(function (section) { section.classList.toggle('hidden', isVisual); });

        if (statusSelect) {
            Array.from(statusSelect.options).forEach(function (option) {
                option.disabled = isVisual ? option.value === 'Tunda' : option.value === 'Segera';
            });
            if (isVisual && statusSelect.value === 'Tunda') statusSelect.value = 'Segera';
            if (!isVisual && statusSelect.value === 'Segera') statusSelect.value = 'Tunda';
        }
        if (severitySelect && !isVisual) severitySelect.value = 'NORMAL';
        if (severitySelect) {
            Array.from(severitySelect.options).forEach(function (option) {
                option.disabled = isVisual ? option.value === 'NORMAL' : option.value !== 'NORMAL';
            });
        }
    }

    if (typeInput && typeInput.tagName === 'SELECT') typeInput.addEventListener('change', updateRuleFields);
    updateRuleFields();
});
</script>
@endpush