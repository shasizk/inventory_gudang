@extends('errors.layout')
@section('code', '429')
@section('title', 'Terlalu banyak permintaan')
@section('message')
    <p>Sistem menerima terlalu banyak permintaan dari perangkat ini dalam waktu singkat.</p>
    <p>Tunggu sekitar satu menit, lalu muat ulang halaman.</p>
@endsection
@section('actions')
    <a class="btn primary" href="javascript:location.reload()">Muat ulang</a>
@endsection
