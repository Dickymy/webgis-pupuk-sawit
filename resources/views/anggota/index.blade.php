@extends('layouts.app')

@section('title', 'Data Kebun')
@section('page-title', 'Data Kebun')
@section('page-subtitle', 'Kelola blok lahan dan anggota kelompok tani')

@section('content')
<div class="space-y-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-2.5 shadow-sm dark:border-slate-700 dark:bg-slate-800" aria-label="Kontrol data anggota">
        <div class="flex flex-col gap-2 xl:flex-row xl:items-center">
            @include('components.data-kebun-tabs')

            <p class="shrink-0 px-1 text-xs text-slate-500 dark:text-slate-400">
                <span class="font-bold text-slate-800 dark:text-slate-100">{{ $anggotas->total() }}</span> anggota terdaftar
            </p>

            <div class="relative min-w-0 flex-1 xl:ml-auto xl:max-w-[300px]">
                <div class="pointer-events-none absolute inset-y-0 left-3 flex items-center">
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="search" id="search-anggota" placeholder="Cari nama anggota..." aria-label="Cari nama anggota"
                    class="min-h-10 w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-xs text-slate-700 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200">
            </div>

            <a href="{{ route('anggota.create') }}"
               class="inline-flex min-h-10 w-full shrink-0 items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 sm:w-auto">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Anggota
            </a>
        </div>
    </section>
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl">
        {{-- Desktop Table --}}
        <div class="overflow-x-auto hidden sm:block rounded-t-2xl">
            <table class="w-full text-sm" id="table-anggota">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Nama Anggota</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">No. HP</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Alamat</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-slate-500 uppercase">Blok</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($anggotas as $anggota)
                    <tr class="hover:bg-slate-50/50 transition-colors">
                        <td class="px-4 py-3.5 text-slate-400">{{ $loop->iteration + ($anggotas->currentPage() - 1) * $anggotas->perPage() }}</td>
                        <td class="px-4 py-3.5 font-semibold text-slate-900">{{ $anggota->nama }}</td>
                        <td class="px-4 py-3.5 text-slate-600 text-xs">{{ $anggota->no_hp ?? '—' }}</td>
                        <td class="px-4 py-3.5 text-slate-600 text-xs max-w-[180px] truncate">{{ $anggota->alamat ?? '—' }}</td>
                        <td class="px-4 py-3.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100 text-xs font-bold">{{ $anggota->blok_lahans_count }}</span>
                        </td>
                        <td class="px-4 py-3.5">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('anggota.edit', $anggota) }}" title="Edit" class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:text-emerald-600 hover:bg-emerald-50 transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST" action="{{ route('anggota.destroy', $anggota) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="button" title="Hapus" onclick="confirmDelete(this.closest('form'), '{{ $anggota->nama }}')" class="p-1.5 rounded-lg border border-slate-200 text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-5 py-12 text-center text-slate-400">Belum ada anggota. <a href="{{ route('anggota.create') }}" class="text-emerald-600 font-semibold hover:underline">Tambah sekarang</a></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Card Layout --}}
        <div class="sm:hidden divide-y divide-slate-100" id="mobile-anggota">
            @forelse($anggotas as $anggota)
            <div class="p-3.5 space-y-1.5">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-800 text-sm truncate">{{ $anggota->nama }}</p>
                        <p class="text-[11px] text-slate-400">{{ $anggota->no_hp ?? 'No HP belum diisi' }}</p>
                    </div>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-100 text-[10px] font-bold flex-shrink-0">{{ $anggota->blok_lahans_count }} blok</span>
                </div>
                @if($anggota->alamat)
                <p class="text-[11px] text-slate-500 truncate">📍 {{ $anggota->alamat }}</p>
                @endif
                <div class="flex items-center gap-2 pt-1">
                    <a href="{{ route('anggota.edit', $anggota) }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 text-blue-700 border border-blue-200 text-xs font-medium rounded-lg hover:bg-blue-100 transition-colors">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        Edit
                    </a>
                    <form method="POST" action="{{ route('anggota.destroy', $anggota) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="button" onclick="confirmDelete(this.closest('form'), '{{ $anggota->nama }}')" class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-red-50 text-red-700 border border-red-200 text-xs font-medium rounded-lg hover:bg-red-100 transition-colors">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus
                        </button>
                    </form>
                </div>
            </div>
            @empty
            <div class="px-5 py-12 text-center text-slate-400">Belum ada anggota. <a href="{{ route('anggota.create') }}" class="text-emerald-600 font-semibold hover:underline">Tambah sekarang</a></div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if($anggotas->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $anggotas->links() }}
        </div>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('search-anggota').addEventListener('input', function() {
    var q = this.value.toLowerCase();
    document.querySelectorAll('#table-anggota tbody tr').forEach(function(row) {
        var nama = row.querySelector('td:nth-child(2)');
        if (nama) row.style.display = nama.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
    document.querySelectorAll('#mobile-anggota > div').forEach(function(card) {
        card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
@endpush
