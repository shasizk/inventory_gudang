@extends('errors.layout')
@section('code', '500')
@section('title', 'Terjadi gangguan di server')
@section('message')
    <p>Permintaan Anda tidak bisa diproses karena ada masalah di server. Ini bukan kesalahan Anda.</p>
    <p>Coba muat ulang halaman. Bila masih terjadi, catat jam kejadiannya dan laporkan ke tim pengelola sistem.</p>
    <p class="code">Waktu: {{ now()->format('d/m/Y H:i:s') }}</p>
@endsection
@section('actions')
    <a class="btn primary" href="javascript:location.reload()">Muat ulang</a>
@endsection
