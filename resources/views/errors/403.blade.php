@extends('errors.layout')
@section('code', '403')
@section('title', 'Akses tidak diizinkan')
@section('message')
    <p>Akun Anda tidak memiliki hak untuk membuka halaman atau melakukan tindakan ini.</p>
    <p>Bila Anda merasa seharusnya bisa, minta Owner atau Admin memeriksa peran akun Anda di menu Akun web.</p>
@endsection
@section('actions')
    <a class="btn primary" href="javascript:history.back()">Kembali</a>
@endsection
