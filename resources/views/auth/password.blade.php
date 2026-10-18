@extends('layouts.app')
@section('title', 'Kata Sandi')
@section('content')
<div class="page-heading"><div><span class="eyebrow">KEAMANAN AKUN</span><h1>Ganti kata sandi</h1><p>Gunakan minimal 12 karakter yang mengandung huruf dan angka.</p></div></div>
<section class="panel narrow"><form method="POST" action="{{ route('password.update') }}">@csrf @method('PUT')<label for="current_password">Kata sandi saat ini</label><input id="current_password" name="current_password" type="password" required autocomplete="current-password"><label for="password">Kata sandi baru</label><input id="password" name="password" type="password" required minlength="12" autocomplete="new-password"><label for="password_confirmation">Ulangi kata sandi baru</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="12" autocomplete="new-password"><button class="button primary" type="submit">Simpan kata sandi</button></form></section>
@endsection
