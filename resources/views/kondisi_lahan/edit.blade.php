@extends('layouts.app')

@section('title', 'Edit Observasi')
@section('page-title', 'Edit Observasi')
@section('page-subtitle', 'Perbarui fakta lapangan lalu hitung ulang hasil analisis')

@section('content')
<div class="w-full">
 <form action="{{ route('kondisi-lahan.update', $kondisiLahan) }}" method="POST" enctype="multipart/form-data" class="space-y-4 sm:space-y-6">
  @csrf
  @method('PUT')
  @include('kondisi_lahan._form')
 </form>
</div>
@endsection