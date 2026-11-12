<?php

namespace Database\Seeders;

use App\Models\SigningOfficial;
use Illuminate\Database\Seeder;

class SigningOfficialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $officials = [
            [
                'code' => 'kaprodi',
                'role_title' => 'Ketua Program Studi',
                'signature_title' => 'Ka. Program Studi',
                'name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
                'display_name' => 'Bagus Ali Akbar, S.Si., M.Kom',
                'nip' => '0410019202',
                'email' => 'kaprodi.si@unsub.ac.id',
                'description' => 'Ketua Program Studi Sistem Informasi Fakultas Ilmu Komputer',
                'order' => 1,
                'is_active' => true,
            ],
            [
                'code' => 'dekan',
                'role_title' => 'Dekan',
                'signature_title' => 'Dekan',
                'name' => 'Dr. TEPI PEIRISAL, M.Si',
                'display_name' => 'Dr. Tepi Peirisal, M.Si',
                'nip' => '0415057001',
                'email' => 'dekan.fik@unsub.ac.id',
                'description' => 'Dekan Fakultas Ilmu Komputer Universitas Subang',
                'order' => 2,
                'is_active' => true,
            ],
            [
                'code' => 'wadek',
                'role_title' => 'Wakil Dekan',
                'signature_title' => 'Wakil Dekan',
                'name' => 'BAMBANG TJAHJO UTOMO, MT',
                'display_name' => 'Bambang Tjahjo Utomo, MT',
                'nip' => '0408086801',
                'email' => 'wadek.fik@unsub.ac.id',
                'description' => 'Wakil Dekan Fakultas Ilmu Komputer Universitas Subang',
                'order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($officials as $official) {
            SigningOfficial::updateOrCreate(
                ['code' => $official['code']],
                $official
            );
        }
    }
}
