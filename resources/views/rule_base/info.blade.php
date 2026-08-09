@extends('layouts.app')

@section('title', 'Penjelasan Rule Based')
@section('page-title', 'Penjelasan Rule Based')
@section('page-subtitle', 'Cara sistem mengolah observasi menjadi rekomendasi')

@section('content')
<div class="w-full space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('rule-base.index') }}" class="inline-flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            Kembali
        </a>
        <span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-300 dark:ring-emerald-800">7 rule aktif: 4 gejala daun + 3 waktu</span>
    </div>

    <section class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-900/20">
        <h2 class="text-lg font-bold text-slate-900 dark:text-white">Cara kerja sistem</h2>
        <p class="mt-1 max-w-5xl text-sm leading-6 text-slate-700 dark:text-slate-200">
            Sistem mencocokkan data observasi dengan rule berbentuk IF–THEN menggunakan metode forward chaining. Rule yang syaratnya terpenuhi akan menghasilkan keterangan kondisi lahan dan saran tindakan. Admin dapat menambah atau memperbarui rule, lalu menggunakannya setelah kondisi, hasil, dan sumber acuannya diperiksa.
        </p>
        <div class="mt-4 grid gap-2 sm:grid-cols-3">
            @foreach([
                ['1', 'Catat observasi', 'Warna daun dan kondisi lapangan'],
                ['2', 'Cocokkan rule', 'Fakta diperiksa pada rule yang digunakan'],
                ['3', 'Tampilkan hasil', 'Kondisi, saran, dosis, dan jadwal'],
            ] as [$number, $title, $description])
                <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-white p-3 dark:border-emerald-800 dark:bg-slate-900">
                    <span class="inline-flex h-8 w-8 flex-none items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-300">{{ $number }}</span>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">{{ $title }}</h3>
                        <p class="text-xs leading-5 text-slate-600 dark:text-slate-300">{{ $description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="grid gap-4 xl:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-600">Rule Diagnosis Visual</p>
            <h2 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Gejala yang diperiksa</h2>
            <div class="mt-4 divide-y divide-slate-200 dark:divide-slate-700">
                @foreach([
                    ['VIS-N-01', 'Daun bagian bawah menguning', 'Kemungkinan kekurangan nitrogen'],
                    ['VIS-K-02', 'Bercak kuning atau transparan pada daun tua', 'Kemungkinan kekurangan kalium'],
                    ['VIS-MG-01', 'Tepi daun tua pada bagian terbuka menguning', 'Perlu pemeriksaan kemungkinan kekurangan magnesium'],
                    ['VIS-B-01', 'Daun muda berbentuk kait atau memendek', 'Perlu pemeriksaan kemungkinan kekurangan boron'],
                ] as [$code, $condition, $result])
                    <div class="py-3 first:pt-0 last:pb-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-600 dark:bg-slate-700 dark:text-slate-200">{{ $code }}</span>
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $condition }}</p>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">Hasil: {{ $result }}.</p>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-bold uppercase tracking-wide text-blue-600">Tiga rule waktu pemupukan</p>
            <h2 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Curah hujan yang diperiksa</h2>
            <div class="mt-4 space-y-3">
                @foreach([
                    ['WAKTU-HUJAN-RENDAH', 'Di bawah 60 mm/bulan', 'Pemupukan ditunda'],
                    ['WAKTU-HUJAN-OPTIMAL', '100–250 mm/bulan', 'Dapat dipupuk jika kondisi lapangan memenuhi syarat'],
                    ['WAKTU-HUJAN-TINGGI', 'Di atas 300 mm/bulan', 'Pemupukan ditunda'],
                ] as [$code, $condition, $result])
                    <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                        <span class="text-[10px] font-bold text-blue-600 dark:text-blue-300">{{ $code }}</span>
                        <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm font-semibold text-slate-900 dark:text-white">{{ $condition }}</p>
                            <p class="text-xs text-slate-600 dark:text-slate-300">{{ $result }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
            <p class="mt-4 text-xs leading-5 text-slate-500 dark:text-slate-400">Curah hujan digunakan untuk menentukan waktu pelaksanaan, bukan untuk mengubah dosis pupuk.</p>
        </article>
    </section>

    <section class="grid gap-4 lg:grid-cols-2">
        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-bold uppercase tracking-wide text-emerald-600">Acuan rekomendasi pupuk</p>
            <h2 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Iyung Pahan (2013)</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Dosis Urea dan KCl mengacu pada Iyung Pahan (2013). Sistem memilih dosis berdasarkan umur atau fase tanaman, kemudian menghitung kebutuhan blok dari jumlah pokok.
            </p>
            <p class="mt-3 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-600 dark:bg-slate-900/50 dark:text-slate-300">
                Rule gejala daun tidak menambah atau mengurangi dosis Urea dan KCl secara otomatis.
            </p>
        </article>

        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-600">Batas penggunaan</p>
            <h2 class="mt-1 text-base font-bold text-slate-900 dark:text-white">Hasil visual adalah indikasi awal, bukan diagnosis pasti</h2>
            <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                <li>• Hasil bergantung pada data observasi yang diisi pengguna.</li>
                <li>• Gejala daun yang mirip perlu diperiksa kembali di lapangan.</li>
                <li>• Analisis daun atau tanah dapat digunakan jika diperlukan untuk memastikan kondisi hara.</li>
            </ul>
        </article>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h2 class="text-base font-bold text-slate-900 dark:text-white">Acuan yang digunakan</h2>
        <div class="mt-3 grid gap-3 lg:grid-cols-3">
            <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">Iyung Pahan (2013)</p>
                <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">Acuan dosis Urea dan KCl.</p>
            </div>
            <a href="https://doi.org/10.22302/iopri.war.warta.v30i1.129" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 p-4 transition hover:border-emerald-400 dark:border-slate-700 dark:hover:border-emerald-700">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">Warta PPKS (2025)</p>
                <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">Acuan gejala visual tanaman dan prinsip pemupukan.</p>
            </a>
            <a href="https://doi.org/10.22302/iopri.war.warta.v26i2.48" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 p-4 transition hover:border-emerald-400 dark:border-slate-700 dark:hover:border-emerald-700">
                <p class="text-sm font-semibold text-slate-900 dark:text-white">Warta PPKS (2021)</p>
                <p class="mt-1 text-xs leading-5 text-slate-600 dark:text-slate-300">Acuan waktu pemupukan berdasarkan curah hujan.</p>
            </a>
        </div>
    </section>
</div>
@endsection