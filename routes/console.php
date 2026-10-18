<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('arsipku:admin {email} {--name=Ketua STT Nusantara Lampung}', function () {
    $email = mb_strtolower(trim($this->argument('email')));
    if (! filter_var($email, FILTER_VALIDATE_EMAIL) || \App\Models\User::where('email', $email)->exists()) {
        $this->error('Email tidak valid atau akun sudah ada.'); return 1;
    }
    $password = $this->secret('Kata sandi (minimal 12 karakter, huruf dan angka)');
    $confirmation = $this->secret('Ulangi kata sandi');
    if ($password !== $confirmation || strlen($password ?? '') < 12 || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/[0-9]/', $password)) {
        $this->error('Kata sandi tidak memenuhi ketentuan atau konfirmasi berbeda.'); return 1;
    }
    \Illuminate\Support\Facades\DB::transaction(function () use ($email, $password) {
        $user = \App\Models\User::create(['name' => $this->option('name'), 'email' => $email, 'password' => $password, 'is_admin' => true, 'is_validator' => true, 'is_active' => true, 'must_change_password' => false]);
        \App\Models\Activity::record($user, 'Akun Ketua dibuat melalui CLI');
    });
    $this->info('Akun Ketua berhasil dibuat.'); return 0;
})->purpose('Membuat akun Ketua tanpa kata sandi bawaan');

Artisan::command('arsipku:reset-password {email}', function () {
    $user = \App\Models\User::where('email', mb_strtolower(trim($this->argument('email'))))->first();
    if (! $user) { $this->error('Akun tidak ditemukan.'); return 1; }
    $password = $this->secret('Kata sandi sementara baru (minimal 12 karakter, huruf dan angka)');
    if ($password !== $this->secret('Ulangi kata sandi') || strlen($password ?? '') < 12 || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/[0-9]/', $password)) {
        $this->error('Kata sandi tidak memenuhi ketentuan atau konfirmasi berbeda.'); return 1;
    }
    \Illuminate\Support\Facades\DB::transaction(function () use ($user, $password) {
        $user->update(['password' => $password, 'must_change_password' => true, 'remember_token' => null]);
        \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $user->id)->delete();
        \App\Models\Activity::create(['action' => 'Pemulihan kata sandi melalui CLI', 'details' => ['user_id' => $user->id]]);
    });
    $this->info('Kata sandi diperbarui dan sesi lama dicabut.'); return 0;
})->purpose('Pemulihan akses oleh pemegang akses terminal hosting');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
