@extends('errors.layout')
@section('code', '503')
@section('title', 'Sistem sedang dalam perawatan')
@section('message')
    <p>Website sedang diperbarui dan akan kembali dalam beberapa saat.</p>
    <p>Bila perawatan berlangsung lebih lama dari yang diumumkan, hubungi tim pengelola sistem.</p>
@endsection
@section('actions')
    <a class="btn primary" href="javascript:location.reload()">Coba lagi</a>
@endsection
