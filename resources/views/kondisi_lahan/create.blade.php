@extends('layouts.app')

@section('title', 'Tambah Observasi')
@section('page-title', 'Tambah Observasi')
@section('page-subtitle', 'Catat fakta lapangan lalu lihat hasil analisis')

@section('content')
<div class="w-full">
 <form action="{{ route('kondisi-lahan.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4 sm:space-y-6">
  @csrf
  @include('kondisi_lahan._form')
 </form>
</div>
@endsection