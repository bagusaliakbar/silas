<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categories = [
            [
                'name' => 'Surat Keputusan (SK)',
                'code' => 'SK',
                'letter_direction' => 'keluar',
                'description' => 'Arsip Surat Keputusan Dekan Fasilkom (Tugas Mengajar, Dosen Pembimbing, Yudisium, Kelulusan, dll)',
                'color' => 'blue',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Dinas / Surat Keluar Resmi',
                'code' => 'SURAT_DINAS',
                'letter_direction' => 'both',
                'description' => 'Arsip naskah dinas kedinasan resmi internal fakultas maupun eksternal antar instansi/lembaga',
                'color' => 'indigo',
                'is_active' => true,
            ],
            [
                'name' => 'Memo (Memorandum)',
                'code' => 'MEMO',
                'letter_direction' => 'both',
                'description' => 'Arsip nota dinas atau memorandum komunikasi dinas internal antar pimpinan atau unit kerja',
                'color' => 'slate',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Pengantar',
                'code' => 'SURAT_PENGANTAR',
                'letter_direction' => 'both',
                'description' => 'Arsip surat pengantar penyerta pengiriman berkas, blanko ijazah/transkrip, permohonan ke rektorat, dsb',
                'color' => 'emerald',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Edaran (SE)',
                'code' => 'SURAT_EDARAN',
                'letter_direction' => 'keluar',
                'description' => 'Arsip Surat Edaran pemberitahuan resmi petunjuk pelaksanaan bagi sivitas akademika fakultas',
                'color' => 'amber',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Undangan',
                'code' => 'UNDANGAN',
                'letter_direction' => 'both',
                'description' => 'Arsip surat undangan rapat dosen, senat fakultas, workshop, webinar, dan kegiatan akademik/kedinasan',
                'color' => 'amber',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Tugas',
                'code' => 'SURAT_TUGAS',
                'letter_direction' => 'keluar',
                'description' => 'Arsip surat tugas pelaksanaan kegiatan tri dharma, pelatihan, kepanitiaan dosen & tendik',
                'color' => 'blue',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Kuasa',
                'code' => 'SURAT_KUASA',
                'letter_direction' => 'keluar',
                'description' => 'Arsip surat pelimpahan kuasa atau wewenang resmi kedinasan fakultas',
                'color' => 'rose',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Pengumuman',
                'code' => 'SURAT_PENGUMUMAN',
                'letter_direction' => 'keluar',
                'description' => 'Arsip pengumuman penting informasi akademik, jadwal, maupun kebijakan bagi mahasiswa & publik',
                'color' => 'indigo',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Pernyataan',
                'code' => 'SURAT_PERNYATAAN',
                'letter_direction' => 'both',
                'description' => 'Arsip surat pernyataan komitmen, kesanggupan mengajar, dan fakta integritas',
                'color' => 'emerald',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Keterangan',
                'code' => 'SURAT_KETERANGAN',
                'letter_direction' => 'keluar',
                'description' => 'Arsip surat keterangan mahasiswa aktif, keterangan lulus/yudisium, bebas pustaka, dll',
                'color' => 'emerald',
                'is_active' => true,
            ],
            [
                'name' => 'Surat Kerjasama (Memorandum of Understanding / MoU)',
                'code' => 'MOU_KERJASAMA',
                'letter_direction' => 'both',
                'description' => 'Dokumen perjanjian kerjasama kemitraan MBKM, magang industri, dan tri dharma perguruan tinggi',
                'color' => 'purple',
                'is_active' => true,
            ],
            [
                'name' => 'Berita Acara',
                'code' => 'BERITA_ACARA',
                'letter_direction' => 'both',
                'description' => 'Arsip berita acara pelaksanaan ujian skripsi/sidang, serah terima berkas, atau musyawarah',
                'color' => 'rose',
                'is_active' => true,
            ],
        ];

        // Update ID 1 (Surat Keputusan Dekan)
        DB::table('archive_categories')->where('id', 1)->update([
            'name' => 'Surat Keputusan (SK)',
            'code' => 'SK',
            'letter_direction' => 'keluar',
            'description' => 'Arsip Surat Keputusan Dekan Fasilkom (Tugas Mengajar, Dosen Pembimbing, Yudisium, Kelulusan, dll)',
            'color' => 'blue',
            'is_active' => true,
            'updated_at' => now(),
        ]);

        // Update ID 2 (Surat Pengantar & Keterangan Mahasiswa -> Surat Keterangan)
        DB::table('archive_categories')->where('id', 2)->update([
            'name' => 'Surat Keterangan',
            'code' => 'SURAT_KETERANGAN',
            'letter_direction' => 'keluar',
            'description' => 'Arsip surat keterangan mahasiswa aktif, yudisium, rekomendasi akademik, dll',
            'color' => 'emerald',
            'is_active' => true,
            'updated_at' => now(),
        ]);

        // Update ID 4 (Undangan Rapat & Kegiatan Kampus -> Surat Undangan)
        DB::table('archive_categories')->where('id', 4)->update([
            'name' => 'Surat Undangan',
            'code' => 'UNDANGAN',
            'letter_direction' => 'both',
            'description' => 'Arsip surat undangan rapat dosen, senat fakultas, workshop, webinar, dan acara akademik',
            'color' => 'amber',
            'is_active' => true,
            'updated_at' => now(),
        ]);

        // Update ID 5 (Kerjasama -> Surat Kerjasama MoU)
        DB::table('archive_categories')->where('id', 5)->update([
            'name' => 'Surat Kerjasama (Memorandum of Understanding / MoU)',
            'code' => 'MOU_KERJASAMA',
            'letter_direction' => 'both',
            'description' => 'Dokumen perjanjian kerjasama kemitraan MBKM, magang industri, dan tri dharma perguruan tinggi',
            'color' => 'purple',
            'is_active' => true,
            'updated_at' => now(),
        ]);

        // Insert missing categories
        foreach ($categories as $cat) {
            $exists = DB::table('archive_categories')
                ->where('code', $cat['code'])
                ->orWhere('name', $cat['name'])
                ->exists();

            if (! $exists) {
                DB::table('archive_categories')->insert(array_merge($cat, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse needed or can keep categories
    }
};
