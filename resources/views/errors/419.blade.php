@extends('errors.layout')
@section('code', '419')
@section('title', 'Sesi login berakhir')
@section('message')
    <p>Halaman terlalu lama dibiarkan terbuka sehingga sesi Anda berakhir. Data yang Anda isi barusan <strong>belum tersimpan</strong>.</p>
    <p>Masuk lagi, lalu ulangi tindakan terakhir Anda.</p>
@endsection
@section('actions')
    <a class="btn primary" href="{{ Route::has('login') ? route('login') : url('/') }}">Masuk lagi</a>
@endsection
