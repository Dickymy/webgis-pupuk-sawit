{{--
    Komponen reusable: Hasil Analisis RBS
    Penggunaan: @include('rbs.partials._hasil_rbs', ['blokLahan' => $blokLahan])
--}}

@if($rbs = $blokLahan->rekomendasiRbsTerbaru)

@php
// Pahan v2.6: Banner utama berdasarkan Kondisi Tanaman dan Kelayakan Aplikasi, BUKAN status_kebutuhan_dominan
$warna = match($rbs->status_kondisi_tanaman) {
    'GEJALA_BERAT' => [
        'bg'     => 'bg-red-50',
        'border' => 'border-red-200',
        'badge'  => 'bg-red-100 text-red-800 ring-1 ring-red-300',
        'icon'   => '🔴',
        'title'  => 'text-red-800',
    ],
    'TERINDIKASI_DEFISIENSI' => [
        'bg'     => 'bg-orange-50',
        'border' => 'border-orange-200',
        'badge'  => 'bg-orange-100 text-orange-800 ring-1 ring-orange-300',
        'icon'   => '🟠',
        'title'  => 'text-orange-800',
    ],
    'NORMAL_VISUAL' => [
        'bg'     => 'bg-emerald-50',
        'border' => 'border-emerald-200',
        'badge'  => 'bg-emerald-100 text-emerald-800 ring-1 ring-emerald-300',
        'icon'   => '✅',
        'title'  => 'text-emerald-800',
    ],
    'PERLU_VERIFIKASI' => [
        'bg'     => 'bg-yellow-50',
        'border' => 'border-yellow-200',
        'badge'  => 'bg-yellow-100 text-yellow-800 ring-1 ring-yellow-300',
        'icon'   => '🟡',
        'title'  => 'text-yellow-800',
    ],
    default => [
        'bg'     => 'bg-blue-50',
        'border' => 'border-blue-200',
        'badge'  => 'bg-blue-100 text-blue-800 ring-1 ring-blue-300',
        'icon'   => 'ℹ️',
        'title'  => 'text-blue-800',
    ],
};
@endphp

<div class="{{ $warna['bg'] }} {{ $warna['border'] }} border rounded-2xl p-5 space-y-4">

    {{-- Header Status --}}
    <div class="flex items-start justify-between gap-3">
        <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
            <span class="text-base">{{ $warna['icon'] }}</span>
            Hasil Analisis RBS
        </h3>
        <div class="flex items-center gap-2 flex-shrink-0">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $warna['badge'] }}">
                {{ $rbs->label_kondisi_tanaman }}
            </span>
            <span class="text-xs text-slate-400">{{ $rbs->tanggal_analisis->format('d M Y') }}</span>
        </div>
    </div>

    {{-- Status Dual: Kondisi Tanaman & Kelayakan Aplikasi (Pahan-v2) --}}
    @if($rbs->status_kondisi_tanaman || $rbs->status_kelayakan_aplikasi)
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
        {{-- Kondisi Tanaman --}}
        <div class="flex items-center gap-2.5 px-3 py-2 rounded-xl border
            @switch($rbs->status_kondisi_tanaman)
                @case('GEJALA_BERAT') bg-red-50 border-red-200 @break
                @case('TERINDIKASI_DEFISIENSI') bg-orange-50 border-orange-200 @break
                @case('NORMAL_VISUAL') bg-emerald-50 border-emerald-200 @break
                @case('PERLU_VERIFIKASI') bg-yellow-50 border-yellow-200 @break
                @default bg-slate-50 border-slate-200
            @endswitch
        ">
            <span class="text-base">
                @switch($rbs->status_kondisi_tanaman)
                    @case('GEJALA_BERAT') 🔴 @break
                    @case('TERINDIKASI_DEFISIENSI') 🟠 @break
                    @case('NORMAL_VISUAL') 🟢 @break
                    @case('PERLU_VERIFIKASI') 🟡 @break
                    @default ⚪
                @endswitch
            </span>
            <div>
                <p class="text-[9px] text-slate-500 uppercase font-bold">Kondisi Tanaman</p>
                <p class="text-xs font-semibold text-slate-800">
                    @switch($rbs->status_kondisi_tanaman)
                        @case('GEJALA_BERAT') Gejala Berat @break
                        @case('TERINDIKASI_DEFISIENSI') Terindikasi Defisiensi @break
                        @case('NORMAL_VISUAL') Kondisi Visual Normal @break
                        @case('PERLU_VERIFIKASI') Perlu Verifikasi @break
                        @case('BELUM_DIOBSERVASI') Belum Diobservasi @break
                        @default Belum Dicek
                    @endswitch
                </p>
            </div>
        </div>
        {{-- Kelayakan Aplikasi --}}
        <div class="flex items-center gap-2.5 px-3 py-2 rounded-xl border
            @if(in_array($rbs->status_kelayakan_aplikasi, ['LAYAK_DIJADWALKAN', 'TERLAMBAT_PERLU_DIJADWALKAN'])) bg-emerald-50 border-emerald-200
            @elseif($rbs->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA') bg-blue-50 border-blue-200
            @else bg-amber-50 border-amber-200
            @endif
        ">
            <span class="text-base">
                @switch($rbs->status_kelayakan_aplikasi)
                    @case('LAYAK_DIJADWALKAN') ✅ @break
                    @case('TERLAMBAT_PERLU_DIJADWALKAN') ⏰ @break
                    @case('TUNDA_HUJAN_RENDAH') ☀️ @break
                    @case('TUNDA_HUJAN_TINGGI') 🌧️ @break
                    @case('TUNDA_INTERVAL') ⏳ @break
                    @case('PERLU_PERBAIKAN_DRAINASE') 💧 @break
                    @case('PERLU_VERIFIKASI_DATA') ❓ @break
                    @default ❓
                @endswitch
            </span>
            <div>
                <p class="text-[9px] text-slate-500 uppercase font-bold">Kelayakan Pemupukan</p>
                <p class="text-xs font-semibold text-slate-800">
                    {{ \App\Services\FertilizationWindowService::labelStatus($rbs->status_kelayakan_aplikasi ?? 'PERLU_VERIFIKASI_DATA') }}
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Masalah Teridentifikasi --}}
    @if($rbs->masalah_teridentifikasi && count($rbs->masalah_teridentifikasi) > 0)
    <div>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Masalah Teridentifikasi</p>
        <div class="flex flex-wrap gap-1.5">
            @foreach($rbs->masalah_teridentifikasi as $masalah)
            <span class="inline-flex items-center px-2.5 py-1 bg-white border border-slate-200 text-slate-700 text-xs rounded-full shadow-sm">
                {{ $masalah }}
            </span>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Rekomendasi Pupuk --}}
    @if($rbs->rekomendasi_pupuk && count($rbs->rekomendasi_pupuk) > 0)
    <div>
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Rekomendasi Pemupukan</p>
        <div class="space-y-2">
            @foreach($rbs->rekomendasi_pupuk as $pupuk)
            @php
                $dosisDisplay = $pupuk['dosis'] ?? '-';
                $metodeDisplay = $pupuk['metode'] ?? '';
                $waktuDisplay = $pupuk['waktu'] ?? '';
                
                // Override dosis dan cara aplikasi agar sinkron dengan kalkulasi kriteria lahan (Urea & KCl)
                // PERBAIKAN v2.2: Tampilkan kebutuhan tahunan, bukan dosis aplikasi saat ini (yang bisa 0 saat ditunda)
                $ureaPerPokok = $rbs->urea_estimasi_kg_per_pokok_tahun ?? $rbs->dosis_urea;
                $kclPerPokok = $rbs->kcl_estimasi_kg_per_pokok_tahun ?? $rbs->dosis_kcl;
                $jmlPokok = $rbs->jumlah_pokok_snapshot ?? 0;
                $ureaTotalTahunan = $ureaPerPokok ? round($ureaPerPokok * $jmlPokok, 1) : $rbs->total_urea;
                $kclTotalTahunan = $kclPerPokok ? round($kclPerPokok * $jmlPokok, 1) : $rbs->total_kcl;

                if (str_contains(strtolower($pupuk['jenis_utama']), 'urea') && $ureaPerPokok) {
                    $dosisDisplay = number_format($ureaPerPokok, 2) . ' kg/pokok/tahun (Total Blok: ' . number_format($ureaTotalTahunan, 1) . ' kg)';
                    $umurTanaman = $blokLahan->umur_tanaman;
                    $kategoriUmur = $blokLahan->kategori_umur;
                    if ($kategoriUmur === 'Belum Menghasilkan' || ($umurTanaman !== null && $umurTanaman < 3)) {
                        $metodeDisplay = 'Ditabur melingkar merata (lebar band 10-20 cm) sekitar 30-50 cm dari pangkal batang sawit (Tanaman Belum Menghasilkan).';
                    } else {
                        $metodeDisplay = 'Ditabur melingkar merata pada piringan bersih berjarak 1.5 - 2.0 meter dari pangkal batang (di bawah proyeksi tajuk terluar pelepah).';
                    }
                } elseif (str_contains(strtolower($pupuk['jenis_utama']), 'kcl') && $kclPerPokok) {
                    $dosisDisplay = number_format($kclPerPokok, 2) . ' kg/pokok/tahun (Total Blok: ' . number_format($kclTotalTahunan, 1) . ' kg)';
                    $umurTanaman = $blokLahan->umur_tanaman;
                    $kategoriUmur = $blokLahan->kategori_umur;
                    if ($kategoriUmur === 'Belum Menghasilkan' || ($umurTanaman !== null && $umurTanaman < 3)) {
                        $metodeDisplay = 'Ditabur melingkar merata sekitar 30-50 cm dari pangkal batang di atas piringan bersih.';
                    } else {
                        $metodeDisplay = 'Ditabur melingkar merata berjarak 1.5 - 2.0 meter dari pangkal batang (di bawah area akar rambut aktif).';
                    }
                }
            @endphp
            <div class="bg-white rounded-xl border border-slate-200 p-3 shadow-sm">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <span class="font-semibold text-emerald-700 text-sm">🌿 {{ $pupuk['jenis_utama'] }}</span>
                    @if(!empty($pupuk['jenis_pendukung']))
                    <span class="text-xs text-slate-400 flex-shrink-0">+ {{ $pupuk['jenis_pendukung'] }}</span>
                    @endif
                </div>
                @if(!empty($dosisDisplay))
                <p class="text-xs text-slate-600"><span class="font-medium">Dosis:</span> {{ $dosisDisplay }}</p>
                @endif
                @if(!empty($metodeDisplay))
                <p class="text-xs text-slate-600 mt-0.5"><span class="font-medium">Cara:</span> {{ $metodeDisplay }}</p>
                @endif
                @if(!empty($waktuDisplay))
                <p class="text-xs text-slate-500 mt-0.5"><span class="font-medium">Waktu:</span> {{ $waktuDisplay }}</p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Saran Tindakan --}}
    @if($rbs->saran_tindakan_utama)
    <div class="bg-white rounded-xl border border-slate-200 p-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Saran Tindakan</p>
        <p class="text-xs text-slate-700 leading-relaxed">{{ $rbs->saran_tindakan_utama }}</p>
    </div>
    @endif

    {{-- Tahap Aktif Saat Ini (Pahan v2.5) --}}
    @if($rbs->active_stage && $rbs->status_stage)
    <div class="bg-white rounded-xl border border-slate-200 p-3">
        <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Tahap Aktif</p>
        <p class="text-xs text-slate-700"><span class="font-bold">Tahap {{ $rbs->active_stage }}:</span> {{ \App\Services\CurrentApplicationCalculator::labelStatusStage($rbs->status_stage) }}</p>
        @if(($rbs->urea_aplikasi_saat_ini ?? 0) > 0)
        <p class="text-xs text-emerald-700 font-semibold mt-1">Urea: {{ number_format($rbs->urea_aplikasi_saat_ini, 1) }} kg · KCl: {{ number_format($rbs->kcl_aplikasi_saat_ini, 1) }} kg</p>
        @endif
        @if($rbs->alasan_tahap)
        <p class="text-[10px] text-slate-500 mt-0.5">{{ $rbs->alasan_tahap }}</p>
        @endif
    </div>
    @endif

    {{-- Catatan Dosis Kontekstual --}}
    @if($rbs->catatan_dosis)
    @php
        // Pahan v2.6: Warna catatan berdasarkan kelayakan, bukan status legacy
        $catatanStyle = match(true) {
            in_array($rbs->status_kelayakan_aplikasi, ['LAYAK_DIJADWALKAN', 'TERLAMBAT_PERLU_DIJADWALKAN']) => 'bg-emerald-50 border-emerald-200 text-emerald-800',
            $rbs->status_kondisi_tanaman === 'GEJALA_BERAT' => 'bg-red-50 border-red-200 text-red-800',
            $rbs->status_kondisi_tanaman === 'TERINDIKASI_DEFISIENSI' => 'bg-blue-50 border-blue-200 text-blue-800',
            default => 'bg-amber-50 border-amber-200 text-amber-800',
        };
    @endphp
    <div class="{{ $catatanStyle }} border rounded-xl p-3">
        <p class="text-xs font-semibold uppercase tracking-wider mb-1 opacity-70">Catatan Aplikasi Dosis</p>
        <p class="text-xs leading-relaxed font-medium">{{ $rbs->catatan_dosis }}</p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="flex items-center justify-between pt-1">
        <p class="text-xs text-slate-400">
            {{ $rbs->jumlah_rule_terpicu }} rule terpicu · {{ $rbs->tanggal_analisis->diffForHumans() }}
        </p>
        @if(!request()->routeIs('rbs.detail'))
        <a href="{{ route('rbs.detail', $blokLahan) }}"
           class="text-xs text-emerald-600 hover:text-emerald-700 font-medium hover:underline">
            Lihat detail →
        </a>
        @endif
    </div>
</div>

@else
<div class="bg-slate-50 border border-dashed border-slate-300 rounded-2xl p-6 text-center">
    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mx-auto mb-3">
        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
        </svg>
    </div>
    <p class="text-slate-500 text-sm font-medium">Belum ada analisis RBS</p>
    <p class="text-xs text-slate-400 mt-1 mb-3">
        @if(!$blokLahan->kondisiTerbaru)
            Input data kondisi lahan terlebih dahulu sebelum menjalankan analisis.
        @else
            Data kondisi tersedia. Klik tombol di bawah untuk memulai analisis.
        @endif
    </p>
    @if($blokLahan->kondisiTerbaru)
        <form action="{{ route('rbs.analisis', $blokLahan) }}" method="POST">
            @csrf
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-xl transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                Jalankan Analisis Sekarang
            </button>
        </form>
    @else
        <a href="{{ route('kondisi-lahan.create', ['blok_lahan_id' => $blokLahan->id]) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-medium rounded-xl transition-colors">
            + Input Kondisi Lahan
        </a>
    @endif
</div>
@endif
