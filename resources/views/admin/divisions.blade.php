@extends('layouts.app')
@section('title', 'Divisi')
@section('content')
<div class="page-heading"><div><span class="eyebrow">ADMINISTRASI</span><h1>Ruang divisi kampus</h1><p>Tambahkan divisi sesuai struktur kampus. Setiap divisi memiliki ruang arsip tersendiri.</p></div><a class="button" href="{{ route('admin.users') }}"><x-icon name="users" size="17"/>Kelola Pengguna</a></div>
<div class="split-panels">
    <section class="panel flush"><div class="panel-header"><h2>Daftar divisi</h2><span class="muted small">{{ $divisions->count() }} divisi</span></div>
        <div class="table-wrap"><table><thead><tr><th>Kode</th><th>Nama divisi</th></tr></thead><tbody>
        @forelse($divisions as $division)
            <tr><td><strong>{{ $division->code }}</strong></td><td>{{ $division->name }}</td></tr>
        @empty
            <tr><td colspan="2">Belum ada divisi. Tambahkan divisi pertama melalui formulir.</td></tr>
        @endforelse
        </tbody></table></div>
    </section>
    <section class="panel"><h2>Tambah divisi</h2><p class="helper">Setelah divisi dibuat, tambahkan akun pimpinannya melalui menu Pengguna. Satu pimpinan aktif per divisi.</p>
        <form method="POST" action="{{ route('admin.divisions.store') }}">@csrf
            <label for="code">Kode divisi</label><input id="code" name="code" required maxlength="20" value="{{ old('code') }}" placeholder="Contoh: UPT-TIK" aria-describedby="division-code-help">
            <p class="helper" id="division-code-help">Maksimal 20 karakter. Gunakan huruf, angka, atau tanda hubung. Kode disimpan dalam huruf kapital.</p>
            <label for="name">Nama divisi</label><input id="name" name="name" required maxlength="255" value="{{ old('name') }}" placeholder="Contoh: Unit Pelaksana Teknis Teknologi Informasi">
            <button class="button primary" type="submit"><x-icon name="plus" size="17"/>Simpan Divisi</button>
        </form>
    </section>
</div>
@endsection
