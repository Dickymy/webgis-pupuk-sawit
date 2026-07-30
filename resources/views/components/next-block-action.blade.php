@props([
    'blok',
    'compact' => false,
    'hideDetailFallback' => false,
])

@php
    $kondisi = $blok->kondisiTerbaru;
    $rekomendasi = $blok->rekomendasiRbsTerbaru;
    $eligibility = $blok->operational_eligibility;
    $bolehMencatat = $eligibility['boleh_mencatat'] ?? ($rekomendasi?->is_tahap_siap
        && (($rekomendasi->urea_aplikasi_saat_ini ?? 0) > 0 || ($rekomendasi->kcl_aplikasi_saat_ini ?? 0) > 0));
    $programSelesai = ($eligibility['status_stage'] ?? null) === 'SELESAI_TAHUNAN'
        || $rekomendasi?->is_program_selesai;
    $dataBelumCukup = $rekomendasi
        && (in_array($rekomendasi->status_kondisi_tanaman, ['PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI'], true)
            || $rekomendasi->status_kelayakan_aplikasi === 'PERLU_VERIFIKASI_DATA');
    $perluDiperbarui = $kondisi && $rekomendasi
        && $kondisi->updated_at->gt($rekomendasi->updated_at);
    $baseClass = $compact
        ? 'inline-flex min-h-11 items-center justify-center rounded-lg px-3 py-2.5 text-[11px] font-semibold transition-colors sm:min-h-0 sm:px-2.5 sm:py-1.5 sm:text-[10px]'
        : 'inline-flex min-h-11 items-center justify-center rounded-xl px-3.5 py-2.5 text-xs font-semibold transition-colors';
@endphp

@if(! $kondisi)
    <a href="{{ route('kondisi-lahan.create', ['blok_lahan_id' => $blok->id]) }}"
       {{ $attributes->class([$baseClass, 'border border-emerald-600 bg-emerald-600 text-white shadow-sm hover:border-emerald-700 hover:bg-emerald-700']) }}>
        Isi Observasi
    </a>
@elseif($dataBelumCukup)
    <a href="{{ route('kondisi-lahan.edit', $kondisi) }}"
       {{ $attributes->class([$baseClass, 'border border-emerald-600 bg-emerald-600 text-white shadow-sm hover:border-emerald-700 hover:bg-emerald-700']) }}>
        Lengkapi Observasi
    </a>
@elseif(! $rekomendasi || $perluDiperbarui)
    <form action="{{ route('rbs.analisis', $blok) }}" method="POST" class="inline">
        @csrf
        <button type="submit"
                class="{{ $baseClass }} border border-emerald-600 bg-emerald-600 text-white shadow-sm hover:border-emerald-700 hover:bg-emerald-700">
            {{ $rekomendasi ? 'Perbarui Rekomendasi' : 'Buat Rekomendasi' }}
        </button>
    </form>
@elseif($bolehMencatat)
    <a href="{{ route('realisasi-pemupukan.create', $rekomendasi) }}"
       {{ $attributes->class([$baseClass, 'border border-emerald-600 bg-emerald-600 text-white shadow-sm hover:border-emerald-700 hover:bg-emerald-700']) }}>
        Catat Realisasi
    </a>
@elseif($programSelesai)
    <a href="{{ route('laporan.show', $rekomendasi) }}"
       {{ $attributes->class([$baseClass, 'border border-emerald-300 bg-white text-emerald-700 hover:border-emerald-500 hover:bg-emerald-50 dark:border-emerald-700 dark:bg-slate-800 dark:text-emerald-300 dark:hover:bg-slate-700']) }}>
        Lihat Laporan
    </a>
@else
    @unless($hideDetailFallback)
        <a href="{{ route('rbs.detail', $blok) }}"
           {{ $attributes->class([$baseClass, 'border border-emerald-300 bg-white text-emerald-700 hover:border-emerald-500 hover:bg-emerald-50 dark:border-emerald-700 dark:bg-slate-800 dark:text-emerald-300 dark:hover:bg-slate-700']) }}>
            Lihat Rekomendasi
        </a>
    @endunless
@endif
