@php
    $rbs = $blokLahan->rekomendasiRbsTerbaru;
    $kondisi = $blokLahan->kondisiTerbaru;
    $readyStages = [
        \App\Services\CurrentApplicationCalculator::TAHAP_1_SIAP,
        \App\Services\CurrentApplicationCalculator::TAHAP_1_SEBAGIAN,
        \App\Services\CurrentApplicationCalculator::TAHAP_2_SIAP,
    ];
    $statusStage = $rbs?->status_stage;
    $isReady = $rbs && in_array($statusStage, $readyStages, true);
    $isWaitingInterval = $statusStage === \App\Services\CurrentApplicationCalculator::MENUNGGU_INTERVAL;
    $isWaitingEligibility = $statusStage === \App\Services\CurrentApplicationCalculator::MENUNGGU_KELAYAKAN;
    $isComplete = $statusStage === \App\Services\CurrentApplicationCalculator::SELESAI_TAHUNAN;
    $needsReview = $statusStage === \App\Services\CurrentApplicationCalculator::PERLU_VERIFIKASI_REALISASI;
    $currentDataReady = $observationCompleteness['can_run_diagnosis'] ?? $rbs?->data_cukup ?? false;
    $dataPendukungKurang = collect($observationCompleteness['missing_fields'] ?? $rbs?->data_kurang ?? [])->filter()->values();
    $needsData = $rbs && (!$currentDataReady || $needsReview);

    $decisionKind = match (true) {
        !$rbs => 'no_result',
        $needsData => 'needs_data',
        $isComplete => 'complete',
        $isWaitingInterval => 'interval',
        $isWaitingEligibility => 'paused',
        $isReady => 'ready',
        default => 'review',
    };
    $decisionLabel = match ($decisionKind) {
        'ready' => 'Siap Dipupuk',
        'interval' => 'Menunggu Jarak Waktu',
        'paused' => 'Belum Saatnya Dipupuk',
        'complete' => 'Program Tahun Ini Selesai',
        'needs_data' => 'Lengkapi Data Observasi',
        'no_result' => 'Belum Ada Hasil Analisis',
        default => 'Periksa Data Terlebih Dahulu',
    };
    $decisionDescription = match ($decisionKind) {
        'ready' => $rbs->alasan_tahap ?: 'Dosis pada tahap aktif sudah dapat diaplikasikan, kemudian dicatat sebagai realisasi.',
        'interval' => 'Tahap sebelumnya telah selesai. Tahap berikutnya dapat dilakukan setelah jarak waktu minimal '.config('fertilization.window.min_interval_days', 120).' hari terpenuhi.'
            . ($rbs?->tanggal_minimum_tahap_berikutnya ? ' Tanggal paling awal: '.$rbs->tanggal_minimum_tahap_berikutnya->format('d/m/Y').'.' : ''),
        'paused' => $rbs->alasan_kelayakan ?: ($rbs->alasan_tahap ?: 'Kondisi lapangan belum memenuhi syarat pemupukan.'),
        'complete' => 'Kebutuhan Urea dan KCl tahunan sudah terpenuhi berdasarkan realisasi yang tercatat.',
        'needs_data' => $rbs->notifikasi_data ?: 'Data belum cukup untuk menghasilkan rekomendasi operasional yang dapat diterapkan.',
        'no_result' => $kondisi
            ? 'Observasi sudah tersedia. Jalankan analisis untuk menghasilkan rekomendasi pupuk.'
            : 'Isi observasi lapangan terlebih dahulu agar sistem memiliki fakta untuk dianalisis.',
        default => $rbs?->alasan_tahap ?: 'Periksa kembali observasi dan realisasi sebelum melanjutkan pemupukan.',
    };
    $decisionStyle = match ($decisionKind) {
        'ready' => ['panel' => 'border-emerald-200 bg-emerald-50/80 dark:border-emerald-800 dark:bg-emerald-950/35', 'icon' => 'bg-emerald-600', 'eyebrow' => 'text-emerald-700 dark:text-emerald-300', 'title' => 'text-emerald-950 dark:text-emerald-100', 'body' => 'text-emerald-800 dark:text-emerald-200'],
        'interval' => ['panel' => 'border-blue-200 bg-blue-50/80 dark:border-blue-800 dark:bg-blue-950/35', 'icon' => 'bg-blue-600', 'eyebrow' => 'text-blue-700 dark:text-blue-300', 'title' => 'text-blue-950 dark:text-blue-100', 'body' => 'text-blue-800 dark:text-blue-200'],
        'complete' => ['panel' => 'border-green-200 bg-green-50/80 dark:border-green-800 dark:bg-green-950/35', 'icon' => 'bg-green-600', 'eyebrow' => 'text-green-700 dark:text-green-300', 'title' => 'text-green-950 dark:text-green-100', 'body' => 'text-green-800 dark:text-green-200'],
        'paused' => ['panel' => 'border-orange-200 bg-orange-50/80 dark:border-orange-800 dark:bg-orange-950/35', 'icon' => 'bg-orange-500', 'eyebrow' => 'text-orange-700 dark:text-orange-300', 'title' => 'text-orange-950 dark:text-orange-100', 'body' => 'text-orange-800 dark:text-orange-200'],
        'needs_data' => ['panel' => 'border-amber-200 bg-amber-50/80 dark:border-amber-800 dark:bg-amber-950/35', 'icon' => 'bg-amber-500', 'eyebrow' => 'text-amber-700 dark:text-amber-300', 'title' => 'text-amber-950 dark:text-amber-100', 'body' => 'text-amber-800 dark:text-amber-200'],
        default => ['panel' => 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800/70', 'icon' => 'bg-slate-600', 'eyebrow' => 'text-slate-600 dark:text-slate-300', 'title' => 'text-slate-950 dark:text-white', 'body' => 'text-slate-700 dark:text-slate-300'],
    };
    $workflow = match ($decisionKind) {
        'ready' => ['Periksa dosis aplikasi saat ini pada kartu Urea dan KCl.', 'Lakukan pemupukan sesuai tahap dan petunjuk aplikasi.', 'Setelah pekerjaan selesai, catat jumlah realisasi yang benar-benar digunakan.'],
        'interval' => ['Jangan lakukan Tahap 2 sebelum tanggal minimum yang ditentukan.', 'Pantau curah hujan dan drainase menjelang jadwal berikutnya.', 'Perbarui observasi, lalu jalankan analisis kembali sebelum pemupukan.'],
        'paused' => ['Periksa alasan pemupukan belum dapat dilakukan pada keputusan di atas.', 'Perbarui data observasi setelah kondisi lapangan berubah.', 'Jalankan analisis kembali untuk memastikan blok sudah siap dipupuk.'],
        'complete' => ['Tidak ada dosis tambahan yang perlu diaplikasikan untuk program tahun ini.', 'Gunakan histori realisasi dan laporan sebagai dokumentasi.', 'Buat observasi baru untuk periode pemupukan berikutnya.'],
        'needs_data' => ['Lengkapi fakta lapangan yang masih kosong pada observasi terbaru.', 'Pastikan curah hujan dan kondisi drainase sudah dicatat.', 'Jalankan analisis kembali setelah observasi diperbarui.'],
        'no_result' => $kondisi
            ? ['Periksa kembali observasi terbaru.', 'Jalankan analisis RBS.', 'Tinjau keputusan dan dosis yang dihasilkan.']
            : ['Isi data observasi lapangan.', 'Simpan fakta kondisi tanaman dan lahan.', 'Jalankan analisis RBS.'],
        default => ['Periksa data observasi dan histori realisasi.', 'Koreksi data yang belum sesuai.', 'Jalankan analisis kembali untuk memperbarui keputusan.'],
    };

    $jumlahPokok = (int) ($rbs?->jumlah_pokok_snapshot ?: round(($blokLahan->luas_ha ?? 0) * ($blokLahan->sph ?? 0)));
    $annualUrea = $rbs?->urea_total_estimasi_tahunan;
    $annualKcl = $rbs?->kcl_total_estimasi_tahunan;
    if ($rbs && $annualUrea === null && $rbs->urea_estimasi_kg_per_pokok_tahun !== null) {
        $annualUrea = round($rbs->urea_estimasi_kg_per_pokok_tahun * $jumlahPokok, 1);
    }
    if ($rbs && $annualKcl === null && $rbs->kcl_estimasi_kg_per_pokok_tahun !== null) {
        $annualKcl = round($rbs->kcl_estimasi_kg_per_pokok_tahun * $jumlahPokok, 1);
    }
    $currentUrea = $rbs?->urea_aplikasi_saat_ini ?? $rbs?->total_urea;
    $currentKcl = $rbs?->kcl_aplikasi_saat_ini ?? $rbs?->total_kcl;
    $saranItems = collect(explode('|', (string) ($rbs?->saran_tindakan_utama ?? '')))->map(fn ($item) => trim($item))->filter()->values();
    $recommendations = collect($rbs?->rekomendasi_pupuk ?? [])->filter(fn ($item) => is_array($item))->unique(fn ($item) => mb_strtolower(trim((string) ($item['jenis_utama'] ?? ''))))->values();
    // Normalisasi snapshot lama yang menyimpan "tidak ada masalah" sebagai satu item.
    $normalProblemPlaceholders = [
        'tidak ada',
        'tidak ada masalah',
        'tidak ada masalah teridentifikasi',
    ];
    $problems = collect($rbs?->masalah_teridentifikasi ?? [])
        ->filter(fn ($problem) => is_string($problem) && trim($problem) !== '')
        ->reject(fn ($problem) => in_array(mb_strtolower(trim($problem)), $normalProblemPlaceholders, true))
        ->values();
    $rules = collect($rbs?->rules_terpicu ?? [])->filter(fn ($item) => is_array($item))->values();
    $schedule = collect($rbs?->jadwal_pemupukan ?? [])->filter(fn ($item) => is_array($item))->values();
    $conditionLabel = $rbs ? \App\Enums\PlantConditionStatus::labelFromValue($rbs->status_kondisi_tanaman) : 'Belum dianalisis';
    $formatKg = fn ($value) => $value === null ? '-' : number_format((float) $value, 1, ',', '.');
    $scheduleStatus = match ($decisionKind) {
        'ready' => 'Dapat dilakukan sekarang',
        'interval' => 'Menunggu tanggal paling awal',
        'paused' => 'Menunggu kondisi lapangan',
        'complete' => 'Program tahun ini selesai',
        'needs_data', 'no_result' => 'Jadwal belum tersedia',
        default => 'Perlu diperiksa kembali',
    };
    $scheduleStatusClass = match ($decisionKind) {
        'ready' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300',
        'interval' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300',
        'complete' => 'bg-green-100 text-green-700 dark:bg-green-900/60 dark:text-green-300',
        'paused' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/60 dark:text-amber-300',
        default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
    };
@endphp

<div class="mx-auto max-w-6xl space-y-4 sm:space-y-5">
    <a href="{{ route('rbs.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-lg px-1 text-sm font-medium text-slate-500 transition-colors hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:text-slate-400 dark:hover:text-emerald-300">
        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Kembali ke Rekomendasi Pupuk
    </a>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900" aria-labelledby="block-summary-title">
        <div class="flex flex-col gap-3 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-slate-800">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Blok yang dianalisis</p>
                <h2 id="block-summary-title" class="mt-0.5 text-lg font-bold text-slate-900 dark:text-white">{{ $blokLahan->nama_blok }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ $blokLahan->nama_pemilik }}</p>
            </div>
            @if($rbs?->tanggal_analisis)
                <p class="text-xs text-slate-500 dark:text-slate-400">Analisis #{{ $rbs->nomor_analisis }} - {{ $rbs->tanggal_analisis->format('d/m/Y') }}</p>
            @endif
        </div>
        <div class="grid grid-cols-2 divide-x divide-y divide-slate-100 sm:grid-cols-4 sm:divide-y-0 dark:divide-slate-800">
            <div class="px-4 py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Luas</p><p class="mt-0.5 text-sm font-bold text-slate-800 dark:text-slate-100">{{ number_format((float) $blokLahan->luas_ha, 2, ',', '.') }} Ha</p></div>
            <div class="px-4 py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Kerapatan</p><p class="mt-0.5 text-sm font-bold text-slate-800 dark:text-slate-100">{{ $blokLahan->sph }} pokok/Ha</p></div>
            <div class="px-4 py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Umur tanaman</p><p class="mt-0.5 text-sm font-bold text-slate-800 dark:text-slate-100">{{ $rbs?->umur_tanaman_snapshot ?? $blokLahan->umur_tanaman ?? '-' }} tahun</p></div>
            <div class="px-4 py-3"><p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Jumlah pokok</p><p class="mt-0.5 text-sm font-bold text-slate-800 dark:text-slate-100">{{ $jumlahPokok > 0 ? number_format($jumlahPokok, 0, ',', '.') : '-' }}</p></div>
        </div>
    </section>

    <section class="rounded-2xl border p-4 shadow-sm sm:p-6 {{ $decisionStyle['panel'] }}" aria-labelledby="decision-title">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex min-w-0 items-start gap-4">
                <div class="flex h-11 w-11 flex-none items-center justify-center rounded-xl text-white {{ $decisionStyle['icon'] }}">
                    @if($decisionKind === 'ready' || $decisionKind === 'complete')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M5 13l4 4L19 7"/></svg>
                    @elseif($decisionKind === 'interval')
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    @else
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.3 4.6l-7 12.1A2 2 0 005 19.7h14a2 2 0 001.7-3l-7-12.1a2 2 0 00-3.4 0z"/></svg>
                    @endif
                </div>
                <div class="min-w-0">
                    <p class="text-xs font-bold uppercase tracking-wider {{ $decisionStyle['eyebrow'] }}">Keputusan saat ini</p>
                    <h2 id="decision-title" class="mt-1 text-2xl font-extrabold sm:text-3xl {{ $decisionStyle['title'] }}">{{ $decisionLabel }}</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 {{ $decisionStyle['body'] }}">{{ $decisionDescription }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full border border-white/70 bg-white/70 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">Kondisi: {{ $conditionLabel }}</span>
                        @if($kondisi?->tanggal_observasi)
                            <span class="rounded-full border border-white/70 bg-white/70 px-2.5 py-1 text-xs font-semibold text-slate-700 shadow-sm dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-200">Observasi: {{ $kondisi->tanggal_observasi->format('d/m/Y') }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex w-full flex-none flex-col gap-2 lg:w-auto">
                @if($isReady)
                    <a href="{{ route('realisasi-pemupukan.create', $rbs) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 lg:w-auto">
                        {{ $statusStage === \App\Services\CurrentApplicationCalculator::TAHAP_2_SIAP ? 'Catat Realisasi Tahap 2' : ($statusStage === \App\Services\CurrentApplicationCalculator::TAHAP_1_SEBAGIAN ? 'Lanjutkan Realisasi Tahap 1' : 'Catat Realisasi Tahap 1') }}
                    </a>
                @elseif(in_array($decisionKind, ['needs_data', 'paused', 'review'], true) && $kondisi)
                    <a href="{{ route('kondisi-lahan.edit', $kondisi) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 dark:bg-white dark:text-slate-900 lg:w-auto">Perbarui Observasi</a>
                @elseif(!$kondisi)
                    <a href="{{ route('kondisi-lahan.create', ['blok_lahan_id' => $blokLahan->id]) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800 lg:w-auto">Isi Observasi</a>
                @elseif(!$rbs)
                    <form action="{{ route('rbs.analisis', $blokLahan) }}" method="POST">@csrf<button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl bg-emerald-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-emerald-800 lg:w-auto">Jalankan Analisis RBS</button></form>
                @elseif($isWaitingInterval || $isComplete)
                    <a href="{{ route('realisasi-pemupukan.index', ['anggota_id' => $blokLahan->anggota_id]) }}" class="inline-flex min-h-11 w-full items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-bold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200 lg:w-auto">Lihat Histori Realisasi</a>
                @endif
            </div>
        </div>
    </section>


    @if($rbs)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900" aria-labelledby="schedule-title">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5 dark:border-slate-800">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-blue-600 dark:text-blue-400">Rencana pelaksanaan</p>
                    <h2 id="schedule-title" class="mt-0.5 text-base font-bold text-slate-900 dark:text-white">Jadwal Pemupukan</h2>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Jadwal tahap aktif berdasarkan observasi dan realisasi terakhir.</p>
                </div>
                <span class="w-fit rounded-full px-2.5 py-1 text-[11px] font-bold {{ $scheduleStatusClass }}">{{ $scheduleStatus }}</span>
            </div>

            <div class="p-4 sm:p-5">
                @if($schedule->isNotEmpty())
                    <div class="space-y-3">
                        @foreach($schedule as $stage)
                            <article class="rounded-xl border border-slate-200 p-3 sm:p-4 dark:border-slate-700">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tahap {{ $stage['tahap'] ?? $loop->iteration }} — Pemupukan Urea dan KCl</h3>
                                    @if(!empty($stage['estimasi_waktu']))
                                        <span class="w-fit rounded-lg bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300">{{ $stage['estimasi_waktu'] }}</span>
                                    @endif
                                </div>
                                <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-3">
                                    <div class="col-span-2 rounded-lg bg-slate-50 p-2.5 sm:col-span-1 dark:bg-slate-800/70"><p class="text-[10px] font-semibold uppercase text-slate-400">Waktu</p><p class="mt-0.5 text-xs font-bold text-slate-800 dark:text-slate-100">{{ $stage['estimasi_waktu'] ?? 'Dapat dilakukan sekarang' }}</p></div>
                                    <div class="rounded-lg bg-amber-50 p-2.5 dark:bg-amber-950/35"><p class="text-[10px] font-semibold uppercase text-amber-700 dark:text-amber-300">Urea</p><p class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white">{{ $formatKg($stage['urea_kg'] ?? 0) }} kg</p></div>
                                    <div class="rounded-lg bg-cyan-50 p-2.5 dark:bg-cyan-950/35"><p class="text-[10px] font-semibold uppercase text-cyan-700 dark:text-cyan-300">KCl</p><p class="mt-0.5 text-sm font-bold text-slate-900 dark:text-white">{{ $formatKg($stage['kcl_kg'] ?? 0) }} kg</p></div>
                                </div>
                                @if(!empty($stage['metode_aplikasi']))<p class="mt-3 text-xs leading-5 text-slate-600 dark:text-slate-300"><strong>Cara melaksanakan:</strong> {{ $stage['metode_aplikasi'] }}</p>@endif
                                @if(!empty($stage['catatan']))<p class="mt-1 text-xs leading-5 text-amber-700 dark:text-amber-300"><strong>Perhatian:</strong> {{ $stage['catatan'] }}</p>@endif
                            </article>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                        <p class="text-sm font-bold text-slate-800 dark:text-slate-100">
                            @if($isWaitingInterval)
                                Tahap 2 belum dapat dilakukan
                            @elseif($isComplete)
                                Tidak ada jadwal tambahan tahun ini
                            @else
                                Jadwal belum dapat dibuat
                            @endif
                        </p>
                        <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">
                            @if($isWaitingInterval && $rbs->tanggal_minimum_tahap_berikutnya)
                                Tahap 2 paling awal dapat dilakukan pada <strong>{{ $rbs->tanggal_minimum_tahap_berikutnya->format('d/m/Y') }}</strong>, setelah jarak waktu minimum terpenuhi.
                            @elseif($isComplete)
                                Kebutuhan tahunan telah selesai berdasarkan realisasi yang tercatat.
                            @else
                                {{ $decisionDescription }}
                            @endif
                        </p>
                    </div>
                @endif
                <p class="mt-3 text-[11px] leading-5 text-slate-500 dark:text-slate-400">Jadwal akan berubah setelah observasi atau realisasi diperbarui. Tahap berikutnya ditampilkan setelah tahap sebelumnya selesai dicatat.</p>
            </div>
        </section>
    @endif

    <div class="grid gap-4 lg:grid-cols-5">
        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-2 dark:border-slate-700 dark:bg-slate-900" aria-labelledby="next-action-title">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-sm font-extrabold text-slate-700 dark:bg-slate-800 dark:text-slate-200">1-3</span>
                <div><p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Urutan pekerjaan</p><h2 id="next-action-title" class="text-base font-bold text-slate-900 dark:text-white">Yang perlu dilakukan</h2></div>
            </div>
            <ol class="mt-4 space-y-3">
                @foreach($workflow as $step)
                    <li class="flex gap-3 text-sm leading-5 text-slate-600 dark:text-slate-300">
                        <span class="flex h-6 w-6 flex-none items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300">{{ $loop->iteration }}</span>
                        <span>{{ $step }}</span>
                    </li>
                @endforeach
            </ol>
            @if($saranItems->isNotEmpty())
                <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/70">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Catatan lapangan dari RBS</p>
                    <p class="mt-1 text-xs leading-5 text-slate-700 dark:text-slate-300">{{ $saranItems->first() }}</p>
                </div>
            @endif
        </section>

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5 lg:col-span-3 dark:border-slate-700 dark:bg-slate-900" aria-labelledby="dose-title">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div><p class="text-xs font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Urea dan KCl</p><h2 id="dose-title" class="text-base font-bold text-slate-900 dark:text-white">Dosis pemupukan</h2></div>
                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $isReady ? 'Gunakan kolom Aplikasi sekarang' : 'Kebutuhan tahunan tetap tersimpan' }}</p>
            </div>

            @if($rbs && ($annualUrea !== null || $annualKcl !== null))
                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                    @foreach([
                        ['name' => 'Urea', 'current' => $currentUrea, 'annual' => $annualUrea, 'per_tree' => $rbs->urea_estimasi_kg_per_pokok_tahun, 'color' => 'amber'],
                        ['name' => 'KCl', 'current' => $currentKcl, 'annual' => $annualKcl, 'per_tree' => $rbs->kcl_estimasi_kg_per_pokok_tahun, 'color' => 'cyan'],
                    ] as $dose)
                        <article class="overflow-hidden rounded-xl border {{ $dose['color'] === 'amber' ? 'border-amber-200 dark:border-amber-800' : 'border-cyan-200 dark:border-cyan-800' }}">
                            <div class="flex items-center justify-between px-3 py-2.5 {{ $dose['color'] === 'amber' ? 'bg-amber-50 dark:bg-amber-950/40' : 'bg-cyan-50 dark:bg-cyan-950/40' }}">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $dose['name'] }}</h3>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ $isReady ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-300' : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400' }}">{{ $isReady ? 'SIAP' : 'BELUM DIAPLIKASIKAN' }}</span>
                            </div>
                            <div class="grid grid-cols-2 divide-x divide-slate-100 dark:divide-slate-800">
                                <div class="p-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Aplikasi sekarang</p>
                                    <p class="mt-1 text-xl font-extrabold text-slate-900 dark:text-white">{{ $formatKg($isReady ? $dose['current'] : 0) }} <span class="text-xs font-semibold text-slate-500">kg</span></p>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $isReady && (float) $dose['current'] > 0 ? 'Sekitar '.ceil((float) $dose['current'] / 50).' karung 50 kg' : 'Tunggu keputusan siap' }}</p>
                                </div>
                                <div class="p-3">
                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Kebutuhan tahunan</p>
                                    <p class="mt-1 text-lg font-bold text-slate-800 dark:text-slate-100">{{ $formatKg($dose['annual']) }} <span class="text-xs font-semibold text-slate-500">kg</span></p>
                                    <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $dose['per_tree'] !== null ? number_format((float) $dose['per_tree'], 2, ',', '.').' kg/pokok/tahun' : 'Dosis per pokok belum tersedia' }}</p>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="mt-3 flex items-start gap-2 rounded-lg bg-blue-50 px-3 py-2.5 text-xs leading-5 text-blue-800 dark:bg-blue-950/40 dark:text-blue-200">
                    <svg class="mt-0.5 h-4 w-4 flex-none" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11v4m0-8h.01M5.1 19h13.8A2.1 2.1 0 0020.7 16L13.8 4a2.1 2.1 0 00-3.6 0L3.3 16A2.1 2.1 0 005.1 19z"/></svg>
                    <p><strong>Acuan dosis:</strong> Iyung Pahan (2013). Angka tahunan dibagi ke tahap aplikasi; angka aplikasi sekarang adalah dosis operasional pada tahap aktif.</p>
                </div>
            @else
                <div class="mt-4 rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center dark:border-slate-700">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Dosis belum dapat ditentukan</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Lengkapi observasi dan jalankan analisis untuk menghitung kebutuhan Urea dan KCl.</p>
                </div>
            @endif
        </section>
    </div>

    @if($rbs)


        <section aria-labelledby="support-title">
            <div class="mb-3"><h2 id="support-title" class="text-base font-bold text-slate-900 dark:text-white">Penjelasan Tambahan</h2><p class="text-xs text-slate-500 dark:text-slate-400">Buka bagian ini hanya jika ingin mengetahui alasan, data, atau riwayat hasil.</p></div>
            <div class="space-y-3">

                <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500 sm:px-5 dark:hover:bg-slate-800/70">
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white">Alasan hasil rekomendasi</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                @if($rules->isEmpty() && $problems->isEmpty())
                                    Tidak ada aturan yang sesuai; periksa kembali data observasi
                                @else
                                    Hasil dijelaskan oleh {{ $rules->count() }} aturan yang sesuai
                                @endif
                            </p>
                        </div>
                        <svg class="h-5 w-5 flex-none text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="space-y-5 border-t border-slate-100 px-4 py-4 sm:px-5 dark:border-slate-800">
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Kesimpulan dan saran</h4>
                            @if($saranItems->isNotEmpty())
                                <ul class="mt-2 space-y-2">
                                    @foreach($saranItems as $item)
                                        <li class="flex gap-2 text-sm leading-5 text-slate-700 dark:text-slate-300"><span class="mt-2 h-1.5 w-1.5 flex-none rounded-full bg-emerald-500"></span><span>{{ $item }}</span></li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Tidak ada saran tambahan.</p>
                            @endif
                        </div>

                        @if($recommendations->isNotEmpty())
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Saran pupuk tambahan</h4>
                                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                    @foreach($recommendations as $item)
                                        <article class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                            <p class="text-sm font-bold text-slate-900 dark:text-white">{{ $item['jenis_utama'] ?? 'Pupuk pendukung' }}</p>
                                            @if(!empty($item['dosis']))<p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $item['dosis'] }}</p>@endif
                                            @if(!empty($item['metode']) || !empty($item['waktu']))
                                                <p class="mt-2 text-[11px] text-slate-500 dark:text-slate-400">{{ $item['metode'] ?? '' }}{{ !empty($item['metode']) && !empty($item['waktu']) ? ' - ' : '' }}{{ $item['waktu'] ?? '' }}</p>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($problems->isNotEmpty() || $rules->isNotEmpty())
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">Mengapa hasil ini muncul?</h4>
                                @if($problems->isNotEmpty())
                                    <div class="mt-2 rounded-xl bg-slate-50 p-3 dark:bg-slate-800/70"><p class="text-[11px] font-semibold text-slate-500 dark:text-slate-400">Temuan dari observasi</p><p class="mt-1 text-sm leading-5 text-slate-700 dark:text-slate-300">{{ $problems->implode('; ') }}</p></div>
                                @endif
                                @if($rules->isNotEmpty())
                                    <div class="mt-2 divide-y divide-slate-100 rounded-xl border border-slate-200 dark:divide-slate-800 dark:border-slate-700">
                                        @foreach($rules as $rule)
                                            <div class="p-3">
                                                <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
                                                    <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $rule['indikasi'] ?? 'Aturan yang sesuai' }}</p>
                                                    @if(!empty($rule['status']))<span class="w-fit rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ \App\Models\RekomendasiRbs::labelStatus($rule['status']) }}</span>@endif
                                                </div>
                                                @if(!empty($rule['sumber_judul']))
                                                    <p class="mt-1 text-[11px] leading-4 text-slate-500 dark:text-slate-400">Acuan: {{ $rule['sumber_penulis'] ?? $rule['sumber_judul'] }}{{ !empty($rule['sumber_tahun']) ? ' ('.$rule['sumber_tahun'].')' : '' }}</p>
                                                @else
                                                    <p class="mt-1 text-[11px] text-amber-700 dark:text-amber-300">Acuan aturan masih perlu dilengkapi pada Rule Based.</p>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </details>


                <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500 sm:px-5 dark:hover:bg-slate-800/70">
                        <div><h3 class="text-sm font-bold text-slate-900 dark:text-white">Data yang digunakan</h3><p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Fakta observasi dan data pendukung analisis</p></div>
                        <svg class="h-5 w-5 flex-none text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="space-y-4 border-t border-slate-100 px-4 py-4 sm:px-5 dark:border-slate-800">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 dark:border-slate-700 dark:bg-slate-800/60">
                            <div class="flex flex-wrap gap-2">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $currentDataReady ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-200' }}">{{ $currentDataReady ? 'Data analisis tersedia' : 'Data analisis belum lengkap' }}</span>
                                <span class="inline-flex rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700">{{ $dataPendukungKurang->isEmpty() ? 'Data pendukung lengkap' : 'Data pendukung perlu dilengkapi' }}</span>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">Kondisi daun digunakan untuk pemeriksaan gejala. Data hujan, kelembapan, drainase, dan riwayat pemupukan digunakan untuk menentukan kesiapan serta jadwal aplikasi.</p>
                        </div>
                        @if($kondisi)
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                                @foreach([
                                    'Kondisi daun' => (config('observation.leaf_condition_labels', [])[$kondisi->warna_daun] ?? $kondisi->warna_daun),
                                    'Curah hujan' => $kondisi->curah_hujan_mm_bulanan ? $kondisi->curah_hujan_mm_bulanan.' mm/bulan' : $kondisi->curah_hujan_kategori,
                                    'Musim' => $kondisi->musim_saat_ini,
                                    'Kelembapan tanah' => $kondisi->kelembaban_tanah,
                                    'Drainase' => $kondisi->kondisi_drainase,
                                    'Hama' => $kondisi->ada_serangan_hama ? 'Ada' : 'Tidak ada',
                                    'Gulma dominan' => $kondisi->ada_gulma_dominan ? 'Ada' : 'Tidak ada',
                                ] as $label => $value)
                                    @if($value !== null && $value !== '')
                                        <div class="rounded-lg bg-slate-50 p-2.5 dark:bg-slate-800/70"><p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $label }}</p><p class="mt-0.5 text-xs font-semibold text-slate-800 dark:text-slate-100">{{ $value }}</p></div>
                                    @endif
                                @endforeach
                            </div>
                            @if($kondisi->foto_observasi_path)
                                <div>
                                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-wide text-slate-400">Foto pendukung (dokumentasi)</p>
                                    <a href="{{ route('kondisi-lahan.photo', $kondisi) }}" target="_blank" class="block w-fit">
                                        <img src="{{ route('kondisi-lahan.photo', $kondisi) }}" alt="Foto observasi {{ $blokLahan->nama_blok }}" class="h-32 w-48 rounded-xl border border-slate-200 object-cover dark:border-slate-700">
                                    </a>
                                </div>
                            @endif
                            @if($dataPendukungKurang->isNotEmpty())
                                <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-800 dark:bg-amber-950/35"><p class="text-xs font-bold text-amber-800 dark:text-amber-200">Data pendukung yang belum tersedia</p><p class="mt-1 text-xs leading-5 text-amber-700 dark:text-amber-300">{{ $dataPendukungKurang->implode(', ') }}</p><p class="mt-1 text-[11px] leading-4 text-amber-700/80 dark:text-amber-300/80">Data ini tidak selalu menghalangi pemeriksaan gejala, tetapi membantu menentukan waktu pemupukan.</p></div>
                            @endif
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('kondisi-lahan.edit', $kondisi) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Edit Observasi</a>
                                <a href="{{ route('kondisi-lahan.create', ['blok_lahan_id' => $blokLahan->id]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-300">Buat Observasi Baru</a>
                            </div>
                        @endif
                    </div>
                </details>

                <details class="group overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
                    <summary class="flex min-h-14 cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-emerald-500 sm:px-5 dark:hover:bg-slate-800/70">
                        <div><h3 class="text-sm font-bold text-slate-900 dark:text-white">Dokumen dan riwayat</h3><p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ isset($historiRekomendasi) ? $historiRekomendasi->count() : 0 }} analisis sebelumnya</p></div>
                        <svg class="h-5 w-5 flex-none text-slate-400 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="space-y-4 border-t border-slate-100 px-4 py-4 sm:px-5 dark:border-slate-800">
                        <div class="flex flex-wrap gap-2">
                            <a href="{{ route('laporan.show', $rbs) }}" class="inline-flex min-h-10 items-center rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200">Lihat Laporan</a>
                            <a href="{{ route('laporan.pdf', $rbs) }}" class="inline-flex min-h-10 items-center rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300">Unduh PDF</a>
                            <a href="{{ route('realisasi-pemupukan.index', ['anggota_id' => $blokLahan->anggota_id]) }}" class="inline-flex min-h-10 items-center rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-50 dark:border-blue-900 dark:text-blue-300">Histori Realisasi</a>
                            @if($kondisi)
                                <form action="{{ route('rbs.analisis', $blokLahan) }}" method="POST">@csrf<button type="submit" class="inline-flex min-h-10 items-center rounded-lg border border-emerald-200 px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 dark:border-emerald-800 dark:text-emerald-300">Jalankan Analisis Ulang</button></form>
                            @endif
                        </div>
                        @if(isset($historiRekomendasi) && $historiRekomendasi->isNotEmpty())
                            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200 dark:divide-slate-800 dark:border-slate-700">
                                @foreach($historiRekomendasi as $history)
                                    <div class="flex items-center justify-between gap-3 p-3">
                                        <div><p class="text-xs font-semibold text-slate-800 dark:text-slate-100">Analisis #{{ $history->nomor_analisis }} - {{ $history->tanggal_analisis->format('d/m/Y') }}</p><p class="mt-0.5 text-[11px] text-slate-500 dark:text-slate-400">{{ \App\Enums\PlantConditionStatus::labelFromValue($history->status_kondisi_tanaman) }} · {{ $history->jumlah_rule_terpicu }} aturan sesuai</p></div>
                                        <a href="{{ route('laporan.show', $history) }}" class="flex-none text-xs font-semibold text-blue-600 hover:underline dark:text-blue-400">Lihat</a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>
            </div>
        </section>

        <p class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-[11px] leading-5 text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
            Rekomendasi merupakan estimasi awal berbasis data blok, observasi visual, dan aturan yang sesuai. Hasil ini tidak menggantikan analisis laboratorium tanah/daun atau keputusan ahli agronomi. Perhitungan kuantitatif aplikasi dibatasi pada Urea dan KCl.
        </p>
    @endif
</div>
