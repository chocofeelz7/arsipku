@extends('layouts.app')
@section('title', 'Profil Saya')
@section('content')
<div class="page-heading"><div><span class="eyebrow">AKUN ANDA</span><h1>Profil saya</h1><p>Perbarui foto yang tampil pada akun Anda.</p></div></div>
<div class="profile-grid">
    <section class="panel profile-summary">
        <x-avatar :user="$user" class="avatar-large"/>
        <h2>{{ $user->name }}</h2><p class="muted small">{{ $user->email }}</p>
        <span class="badge badge-approved">{{ $user->is_admin ? 'Ketua · Administrator' : $user->division?->code }}</span>
        <p class="helper">{{ $user->is_admin ? 'Sekolah Tinggi Teknologi Nusantara Lampung' : $user->division?->name }}</p>
        <a class="button" href="{{ route('password.edit') }}">Ganti kata sandi</a>
    </section>
    <section class="panel"><h2>Ubah foto profil</h2><p class="muted small">Gunakan foto yang jelas. Foto ditampilkan dalam bingkai lingkaran.</p>
        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
            @csrf @method('PUT')
            <label for="photo">Pilih foto baru</label>
            <input id="photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required aria-describedby="photo-help">
            <p class="helper" id="photo-help">JPG, PNG, atau WebP · maksimal 2 MB · dimensi maksimal 4096 × 4096 piksel. Foto hanya tersedia melalui akun yang sedang masuk.</p>
            <button class="button primary" type="submit"><x-icon name="upload" size="17"/>Simpan Foto Profil</button>
        </form>
        @if($user->profile_photo_path)
        <form class="profile-remove" method="POST" action="{{ route('profile.destroy') }}" data-confirm="Hapus foto profil dan gunakan kembali inisial nama?">
            @csrf @method('DELETE')<button class="button danger" type="submit">Hapus Foto</button>
        </form>
        @endif
    </section>
</div>
@endsection
