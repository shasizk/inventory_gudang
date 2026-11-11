@extends('errors.layout')
@section('code', '404')
@section('title', 'Halaman tidak ditemukan')
@section('message')
    <p>Alamat yang Anda buka tidak ada, atau datanya sudah dihapus.</p>
    <p>Periksa kembali alamat di browser, atau buka halaman lewat menu di sidebar.</p>
@endsection
@section('actions')
    <a class="btn primary" href="javascript:history.back()">Kembali</a>
@endsection
