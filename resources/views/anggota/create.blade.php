@extends('layouts.app')

@section('title', 'Tambah Anggota')
@section('page-title', 'Tambah Anggota')
@section('page-subtitle', 'Daftarkan anggota kelompok tani baru')

@section('content')
<div class="w-full">
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-6">
        <form method="POST" action="{{ route('anggota.store') }}" class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            @csrf

            <div>
                <label for="nama" class="block text-sm font-medium text-slate-700 mb-2">Nama Anggota <span class="text-red-400">*</span></label>
                <input type="text" id="nama" name="nama" value="{{ old('nama') }}" required placeholder="Nama lengkap anggota"
                    class="w-full px-4 py-3 bg-white border {{ $errors->has('nama') ? 'border-red-400' : 'border-slate-300' }} rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
                @error('nama') <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="no_hp" class="block text-sm font-medium text-slate-700 mb-2">No. HP <span class="text-xs text-slate-400 font-normal">(opsional)</span></label>
                <input type="text" id="no_hp" name="no_hp" value="{{ old('no_hp') }}" placeholder="08xxxxxxxxxx"
                    class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors">
            </div>

            <div class="lg:col-span-2">
                <label for="alamat" class="block text-sm font-medium text-slate-700 mb-2">Alamat <span class="text-xs text-slate-400 font-normal">(opsional)</span></label>
                <textarea id="alamat" name="alamat" rows="2" placeholder="Alamat anggota"
                    class="w-full px-4 py-3 bg-white border border-slate-300 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-emerald-500 focus:border-emerald-500 transition-colors resize-none">{{ old('alamat') }}</textarea>
            </div>

            <div class="flex flex-col gap-3 pt-2 sm:flex-row lg:col-span-2">
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold rounded-xl transition-all hover:shadow-lg hover:shadow-emerald-600/20">
                    Simpan Anggota
                </button>
                <a href="{{ route('anggota.index') }}" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 text-sm font-medium rounded-xl transition-colors">
                    Batal
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
