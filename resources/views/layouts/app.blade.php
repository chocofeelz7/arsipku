<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#12318a"><meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · ArsipKU</title>
    <link rel="stylesheet" href="{{ asset('app.css').'?v=1.1.0' }}"><script src="{{ asset('app.js').'?v=1.1.0' }}" defer></script>
</head>
<body>
<a class="skip" href="#main">Langsung ke konten</a>
<aside class="sidebar" id="sidebar">
    <a class="brand" href="{{ route('dashboard') }}"><span class="brand-mark"><x-icon name="folder" size="25"/></span><span>Arsip<span class="brand-accent">KU</span><small>ARSIP DIGITAL KAMPUS</small></span></a>
    <div class="workspace"><img class="workspace-logo" src="{{ asset('images/sttn-lampung.png') }}" alt="Logo STTN Lampung" width="42" height="42"><div>STTN Lampung<small>Ruang kerja institusi</small></div></div>
    <p class="nav-label">RUANG KERJA</p>
    <nav aria-label="Navigasi utama">
        <a class="nav-link {{ request()->routeIs('dashboard') ? 'selected' : '' }}" href="{{ route('dashboard') }}"><x-icon name="grid"/>Dashboard</a>
        <a class="nav-link {{ request()->routeIs('documents.*') && !request()->boolean('active') ? 'selected' : '' }}" href="{{ route('documents.index') }}"><x-icon name="folder"/>{{ auth()->user()->is_admin ? 'Seluruh Dokumen' : 'Dokumen Divisi' }}</a>
        <a class="nav-link {{ request()->boolean('active') ? 'selected' : '' }}" href="{{ route('documents.index', ['active' => 1]) }}"><x-icon name="shield"/>Arsip Aktif</a>
        @if(auth()->user()->is_admin && auth()->user()->is_validator)
        <a class="nav-link {{ request()->routeIs('validation.*') ? 'selected' : '' }}" href="{{ route('validation.index') }}"><x-icon name="check"/>Validasi Dokumen</a>
        @endif
        <a class="nav-link {{ request()->routeIs('activities') ? 'selected' : '' }}" href="{{ route('activities') }}"><x-icon name="clock"/>Riwayat Aktivitas</a>
        <a class="nav-link {{ request()->routeIs('inbox*') ? 'selected' : '' }}" href="{{ route('inbox') }}"><x-icon name="bell"/>Notifikasi @if($unreadCount)<span class="nav-count">{{ $unreadCount }}</span>@endif</a>
        @if(auth()->user()->is_admin)
        <p class="nav-label">ADMINISTRASI</p>
        <a class="nav-link {{ request()->routeIs('admin.users*') ? 'selected' : '' }}" href="{{ route('admin.users') }}"><x-icon name="users"/>Pengguna</a>
        <a class="nav-link {{ request()->routeIs('admin.divisions*') ? 'selected' : '' }}" href="{{ route('admin.divisions') }}"><x-icon name="grid"/>Divisi</a>
        <a class="nav-link {{ request()->routeIs('admin.categories*') ? 'selected' : '' }}" href="{{ route('admin.categories') }}"><x-icon name="tag"/>Kategori Dokumen</a>
        @endif
    </nav>
    <div class="sidebar-bottom"><div class="access-note"><x-icon name="lock" size="18"/><span>{{ auth()->user()->is_admin ? 'Akses institusi' : 'Akses terbatas divisi' }}<small>{{ auth()->user()->is_admin ? 'Administrator & Validator' : auth()->user()->division?->code }}</small></span></div><div class="sidebar-foot">Sekolah Tinggi Teknologi<br>Nusantara Lampung</div></div>
</aside>
<div class="app-shell">
    <header class="topbar"><div class="topbar-left"><button class="icon-button mobile-menu" type="button" aria-controls="sidebar" aria-expanded="false" aria-label="Buka navigasi" data-menu><x-icon name="menu"/></button><img class="topbar-campus-logo" src="{{ asset('images/sttn-lampung.png') }}" alt="Logo STTN Lampung" width="28" height="28"><span class="breadcrumb">Ruang Kerja <span>/</span> <strong>@yield('title', 'Dashboard')</strong></span></div><div class="topbar-right">@if(app()->environment('local'))<span class="local-label">LOKAL</span>@endif<a class="icon-button notification-icon" href="{{ route('inbox') }}" aria-label="Notifikasi, {{ $unreadCount }} belum dibaca"><x-icon name="bell"/>@if($unreadCount)<i></i>@endif</a><details class="account-menu"><summary><x-avatar :user="auth()->user()"/><span class="user-label">{{ auth()->user()->name }}<small>{{ auth()->user()->is_admin ? 'Ketua · Administrator' : auth()->user()->division?->code }}</small></span></summary><div class="account-dropdown"><a href="{{ route('profile.edit') }}">Profil saya</a><a href="{{ route('password.edit') }}">Ganti kata sandi</a><form method="POST" action="{{ route('logout') }}">@csrf<button type="submit">Keluar aplikasi</button></form></div></details></div></header>
    <main id="main" class="main-content">
        @if(session('success'))<div class="alert success" role="status"><x-icon name="check"/>{{ session('success') }}</div>@endif
        @if(session('notice'))<div class="alert notice" role="status">{{ session('notice') }}</div>@endif
        @if($errors->any())<div class="alert error" role="alert"><div><strong>Periksa kembali isian Anda.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
        @yield('content')
        <footer class="page-footer"><span>ArsipKU · STTN Lampung</span><span class="developer-credit">Dikembangkan oleh <strong>Faiq Nurraihan</strong></span></footer>
    </main>
</div>
</body></html>
