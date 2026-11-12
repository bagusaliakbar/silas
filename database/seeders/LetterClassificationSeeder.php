<?php

namespace Database\Seeders;

use App\Models\LetterClassification;
use Illuminate\Database\Seeder;

class LetterClassificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classifications = [
            [
                'order' => 1,
                'code' => 'PD.1.1/FIK-US',
                'name' => 'Hubungan Dengan Dosen',
                'category' => 'Pendidikan (PD.1)',
                'description' => 'Surat yang berkaitan dengan hubungan dosen, tugas mengajar, SK, dan undangan rapat akademik.',
            ],
            [
                'order' => 2,
                'code' => 'PD.1.2/FIK-US',
                'name' => 'Perkuliahan (Silabus & Kurikulum)',
                'category' => 'Pendidikan (PD.1)',
                'description' => 'Surat permohonan kuliah, izin PKL, magang, observasi, dan perkuliahan.',
            ],
            [
                'order' => 3,
                'code' => 'PD.1.3/FIK-US',
                'name' => 'Yang Berhubungan Dengan Ujian',
                'category' => 'Pendidikan (PD.1)',
                'description' => 'Surat permohonan ujian susulan, izin sidang TA, seminar UP, yudisium.',
            ],
            [
                'order' => 4,
                'code' => 'PD.1.4/FIK-US',
                'name' => 'Perihal Litbang',
                'category' => 'Pendidikan (PD.1)',
                'description' => 'Penelitian dan pengembangan, izin penelitian skripsi / riset.',
            ],
            [
                'order' => 5,
                'code' => 'PD.1.5/FIK-US',
                'name' => 'Hal Perpustakaan',
                'category' => 'Pendidikan (PD.1)',
                'description' => 'Layanan dan urusan kepustakaan.',
            ],
            [
                'order' => 6,
                'code' => 'PD.1.6/FIK-US',
                'name' => 'Umum',
                'category' => 'Pendidikan (PD.1)',
                'description' => 'Urusan umum bidang pendidikan.',
            ],
            [
                'order' => 7,
                'code' => 'PD.2.1/FIK-US',
                'name' => 'Keuangan',
                'category' => 'Administrasi & Keuangan (PD.2)',
                'description' => 'Perihal keuangan dan anggaran.',
            ],
            [
                'order' => 8,
                'code' => 'PD.2.2/FIK-US',
                'name' => 'Personalia',
                'category' => 'Administrasi & Keuangan (PD.2)',
                'description' => 'Kepegawaian dan data personalia.',
            ],
            [
                'order' => 9,
                'code' => 'PD.2.3/FIK-US',
                'name' => 'Perlengkapan',
                'category' => 'Administrasi & Keuangan (PD.2)',
                'description' => 'Sarana, prasarana, dan perlengkapan.',
            ],
            [
                'order' => 10,
                'code' => 'PD.2.4/FIK-US',
                'name' => 'Urusan Dalam',
                'category' => 'Administrasi & Keuangan (PD.2)',
                'description' => 'Rumah tangga dan urusan dalam fakultas.',
            ],
            [
                'order' => 11,
                'code' => 'PD.2.5/FIK-US',
                'name' => 'Humas',
                'category' => 'Administrasi & Keuangan (PD.2)',
                'description' => 'Hubungan masyarakat dan publikasi.',
            ],
            [
                'order' => 12,
                'code' => 'PD.2.6/FIK-US',
                'name' => 'Bidang Data',
                'category' => 'Administrasi & Keuangan (PD.2)',
                'description' => 'Pengolahan dan dokumentasi data administrasi.',
            ],
            [
                'order' => 13,
                'code' => 'PD.2.7/FIK-US',
                'name' => 'Umum',
                'category' => 'Administrasi & Keuangan (PD.2)',
                'description' => 'Urusan umum administrasi fakultas.',
            ],
            [
                'order' => 14,
                'code' => 'PD.3.1/FIK-US',
                'name' => 'Pembinaan Kemahasiswaan (Ospek, Kesenian, Olah Raga)',
                'category' => 'Kemahasiswaan (PD.3)',
                'description' => 'Kegiatan mahasiswa, minat bakat, seni, olahraga, dan ospek.',
            ],
            [
                'order' => 15,
                'code' => 'PD.3.2/FIK-US',
                'name' => 'Kesejahteraan Mahasiswa',
                'category' => 'Kemahasiswaan (PD.3)',
                'description' => 'Beasiswa, bantuan dan kesejahteraan mahasiswa.',
            ],
            [
                'order' => 16,
                'code' => 'PD.3.3/FIK-US',
                'name' => 'Upaya Pengembangan Daya Penalaran Mahasiswa',
                'category' => 'Kemahasiswaan (PD.3)',
                'description' => 'Lomba karya ilmiah, debat, dan penalaran.',
            ],
            [
                'order' => 17,
                'code' => 'PD.3.4/FIK-US',
                'name' => 'Kerjasama Dengan Fakultas Dilingkungan Universitas',
                'category' => 'Kemahasiswaan (PD.3)',
                'description' => 'Kolaborasi antar-fakultas dalam universitas.',
            ],
            [
                'order' => 18,
                'code' => 'PD.3.5/FIK-US',
                'name' => 'Penciptaan Iklim Pendidikan Yang Baik Dalam Kampus',
                'category' => 'Kemahasiswaan (PD.3)',
                'description' => 'Lingkungan kampus dan ketertiban akademik.',
            ],
            [
                'order' => 19,
                'code' => 'PD.3.6/FIK-US',
                'name' => 'Pengabdian Masyarakat',
                'category' => 'Kemahasiswaan (PD.3)',
                'description' => 'Kegiatan pengabdian masyarakat (KKN, bakti sosial).',
            ],
            [
                'order' => 20,
                'code' => 'PD.3.7/FIK-US',
                'name' => 'Pengolahan Data',
                'category' => 'Kemahasiswaan (PD.3)',
                'description' => 'Pengolahan dan statistik data kemahasiswaan.',
            ],
            [
                'order' => 21,
                'code' => 'PD.3.8/FIK-US',
                'name' => 'Umum',
                'category' => 'Kemahasiswaan (PD.3)',
                'description' => 'Urusan umum bidang kemahasiswaan.',
            ],
        ];

        foreach ($classifications as $item) {
            LetterClassification::updateOrCreate(
                ['code' => $item['code']],
                $item
            );
        }
    }
}
