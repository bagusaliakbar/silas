<?php

namespace Database\Seeders;

use App\Models\ArchiveCategory;
use Illuminate\Database\Seeder;

class ArchiveCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Surat Keputusan (SK) Dekan',
                'code' => 'SK_DEKAN',
                'letter_direction' => 'keluar',
                'description' => 'Arsip Surat Keputusan Dekan Fasilkom (Tugas Mengajar, Dosen Pembimbing, Yudisium, dll)',
                'color' => 'blue',
            ],
            [
                'name' => 'Surat Pengantar & Keterangan Mahasiswa',
                'code' => 'KET_MHS',
                'letter_direction' => 'keluar',
                'description' => 'Arsip Surat Keterangan Mahasiswa Aktif, Pengantar PKL/Magang, Observasi, Izin Penelitian, dll',
                'color' => 'emerald',
            ],
            [
                'name' => 'Surat Masuk Kedinasan & Eksternal',
                'code' => 'MASUK_DINAS',
                'letter_direction' => 'masuk',
                'description' => 'Arsip surat resmi masuk dari Rektorat, Kementerian, LLDIKTI, Instansi Mitra, atau Dinas Pemda',
                'color' => 'indigo',
            ],
            [
                'name' => 'Undangan Rapat & Kegiatan Kampus',
                'code' => 'UNDANGAN',
                'letter_direction' => 'both',
                'description' => 'Arsip surat undangan rapat dosen, senat fakultas, workshop, webinar, dan acara akademik',
                'color' => 'amber',
            ],
            [
                'name' => 'Kerjasama & Nota Kesepahaman (MoU / MoA)',
                'code' => 'MOU_KERJASAMA',
                'letter_direction' => 'both',
                'description' => 'Dokumen perjanjian kerjasama kemitraan MBKM, magang industri, dan tri dharma perguruan tinggi',
                'color' => 'purple',
            ],
            [
                'name' => 'Arsip Berkas & Dokumen Lainnya',
                'code' => 'LAINNYA',
                'letter_direction' => 'both',
                'description' => 'Dokumen pelengkap, formulir Google Form, berkas akreditasi, dan lampiran administratif',
                'color' => 'slate',
            ],
        ];

        foreach ($categories as $cat) {
            ArchiveCategory::updateOrCreate(['code' => $cat['code']], $cat);
        }
    }
}

