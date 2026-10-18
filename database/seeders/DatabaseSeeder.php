<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        foreach ([
            'WAKIL-I' => 'Wakil I Bidang Akademik',
            'WAKIL-II' => 'Wakil II Bidang Keuangan dan Kepegawaian',
            'WAKIL-III' => 'Wakil III Bidang Kemahasiswaan dan Kerjasama',
            'LPPM' => 'LPPM',
            'LPM' => 'LPM',
            'BAAK' => 'Biro Administrasi Akademik Kemahasiswaan',
        ] as $code => $name) {
            \App\Models\Division::firstOrCreate(['code' => $code], ['name' => $name]);
        }
        foreach (['Surat Keputusan', 'Surat Masuk', 'Surat Keluar', 'Pedoman & Kebijakan', 'Laporan Kegiatan', 'Dokumen Kerja Sama', 'Dokumen Keuangan', 'Dokumen Kepegawaian'] as $name) {
            \App\Models\Category::firstOrCreate(['name' => $name]);
        }
    }
}
