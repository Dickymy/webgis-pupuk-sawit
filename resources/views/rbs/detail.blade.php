@extends('layouts.app')

@section('title', 'Detail Analisis — ' . $blokLahan->nama_blok)
@section('page-title', 'Detail Analisis')
@section('page-subtitle', $blokLahan->nama_blok . ' · ' . $blokLahan->nama_pemilik)

@section('content')
@include('rbs.partials._detail_readable')
@endsection
