@props([
    'recommendation',
    'showStage' => true,
    'stageStatus' => null,
    'showFeasibility' => true,
    'compact' => false,
])

@php
    $condition = $recommendation?->status_kondisi_tanaman;
    [$conditionLabel, $conditionClass] = match ($condition) {
        'NORMAL_VISUAL' => ['Tidak ditemukan gejala pada daun', 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800'],
        'TERINDIKASI_DEFISIENSI_RINGAN' => ['Ditemukan gejala pada daun', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800'],
        'TERINDIKASI_DEFISIENSI' => ['Ditemukan gejala pada daun', 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-900/30 dark:text-orange-300 dark:ring-orange-800'],
        'GEJALA_BERAT' => ['Ditemukan gejala pada daun', 'bg-red-50 text-red-700 ring-red-200 dark:bg-red-900/30 dark:text-red-300 dark:ring-red-800'],
        'PERLU_VERIFIKASI', 'BELUM_DIOBSERVASI' => ['Data pemeriksaan belum lengkap', 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800'],
        default => ['Belum dianalisis', 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600'],
    };

    $feasibility = $recommendation?->status_kelayakan_aplikasi;
    [$feasibilityLabel, $feasibilityClass] = match ($feasibility) {
        'LAYAK_DIJADWALKAN', 'TERLAMBAT_PERLU_DIJADWALKAN' => ['Siap dipupuk', 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800'],
        'PERLU_VERIFIKASI_DATA', null => ['Data pemeriksaan belum lengkap', 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800'],
        default => ['Belum saatnya dipupuk', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800'],
    };

    $stage = $stageStatus ?? $recommendation?->status_stage;
    [$stageLabel, $stageClass] = match ($stage) {
        'TAHAP_1_SIAP' => ['Tahap 1 siap', 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800'],
        'TAHAP_1_SEBAGIAN' => ['Tahap 1 sebagian', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800'],
        'TAHAP_2_SIAP' => ['Tahap 2 siap', 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800'],
        'MENUNGGU_INTERVAL' => ['Menunggu jarak waktu', 'bg-blue-50 text-blue-700 ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800'],
        'MENUNGGU_KELAYAKAN' => ['Menunggu kondisi mendukung', 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-900/30 dark:text-amber-300 dark:ring-amber-800'],
        'SELESAI_TAHUNAN' => ['Selesai dipupuk', 'bg-green-50 text-green-700 ring-green-200 dark:bg-green-900/30 dark:text-green-300 dark:ring-green-800'],
        default => ['Belum dilaksanakan', 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-700 dark:text-slate-300 dark:ring-slate-600'],
    };

    $badgeClass = $compact
        ? 'px-1.5 py-0.5 text-[9px]'
        : 'px-2 py-1 text-[10px]';
@endphp

<div {{ $attributes->class(['flex flex-wrap items-center gap-1.5']) }}>
    <span class="inline-flex items-center rounded-full font-semibold ring-1 ring-inset {{ $badgeClass }} {{ $conditionClass }}"
          title="Kondisi tanaman">
        {{ $conditionLabel }}
    </span>
    @if($showFeasibility && $feasibilityLabel !== $conditionLabel)
        <span class="inline-flex items-center rounded-full font-semibold ring-1 ring-inset {{ $badgeClass }} {{ $feasibilityClass }}"
              title="Kesiapan pemupukan">
            {{ $feasibilityLabel }}
        </span>
    @endif
    @if($showStage)
        <span class="inline-flex items-center rounded-full font-semibold ring-1 ring-inset {{ $badgeClass }} {{ $stageClass }}"
              title="Pelaksanaan pemupukan">
            {{ $stageLabel }}
        </span>
    @endif
</div>
