<?php

namespace Database\Seeders;

use App\Models\Course;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = [
            [
                'code' => 'D1A12004',
                'name' => 'Desain Web',
                'sks' => 2,
                'semester' => 2,
                'study_program' => 'Sistem Informasi',
                'description' => 'Mata kuliah dasar desain dan antarmuka web modern.',
                'is_active' => true,
            ],
            [
                'code' => 'D1A22008',
                'name' => 'Rekayasa Perangkat Lunak',
                'sks' => 3,
                'semester' => 4,
                'study_program' => 'Sistem Informasi',
                'description' => 'Mata kuliah konsep SDLC, perancangan, dan arsitektur perangkat lunak.',
                'is_active' => true,
            ],
            [
                'code' => 'D1A31012',
                'name' => 'Jaringan Komputer',
                'sks' => 3,
                'semester' => 3,
                'study_program' => 'Sistem Informasi',
                'description' => 'Mata kuliah konfigurasi dan protokol jaringan komputer.',
                'is_active' => true,
            ],
            [
                'code' => 'D1A41015',
                'name' => 'Audit Sistem Informasi',
                'sks' => 3,
                'semester' => 6,
                'study_program' => 'Sistem Informasi',
                'description' => 'Mata kuliah tata kelola TI dan audit berbasis standar COBIT/ISO.',
                'is_active' => true,
            ],
            [
                'code' => 'D1A73001',
                'name' => 'Kerja Praktek (PKL)',
                'sks' => 3,
                'semester' => 7,
                'study_program' => 'Sistem Informasi',
                'description' => 'Praktik kerja magang di instansi atau perusahaan.',
                'is_active' => true,
            ],
            [
                'code' => 'D1A74102',
                'name' => 'Praktek Analisis dan Perancangan Sistem Lanjut',
                'sks' => 4,
                'semester' => 6,
                'study_program' => 'Sistem Informasi',
                'description' => 'Praktikum mendalam analisis bisnis dan perancangan sistem informasi.',
                'is_active' => true,
            ],
            [
                'code' => 'D1A11001',
                'name' => 'Konsep Sistem Informasi',
                'sks' => 3,
                'semester' => 1,
                'study_program' => 'Sistem Informasi',
                'description' => 'Pengenalan dasar ekosistem sistem informasi.',
                'is_active' => true,
            ],
            [
                'code' => 'D1A51020',
                'name' => 'Analisis dan Perancangan Sistem Informasi I (APSI I)',
                'sks' => 3,
                'semester' => 5,
                'study_program' => 'Sistem Informasi',
                'description' => 'Dasar-dasar rekayasa kebutuhan dan pemodelan UML.',
                'is_active' => true,
            ],
            [
                'code' => 'D1A84001',
                'name' => 'Skripsi / Tugas Akhir',
                'sks' => 6,
                'semester' => 8,
                'study_program' => 'Sistem Informasi',
                'description' => 'Penyusunan laporan karya ilmiah akhir sarjana.',
                'is_active' => true,
            ],
        ];

        foreach ($courses as $course) {
            Course::updateOrCreate(
                ['code' => $course['code']],
                $course
            );
        }
    }
}
