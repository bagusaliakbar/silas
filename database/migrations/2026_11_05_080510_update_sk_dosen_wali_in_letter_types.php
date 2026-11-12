<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('letter_types')
            ->where('code', 'sk_dekan_dosen_wali')
            ->update([
                'name' => 'SK Dosen Wali Akademik',
                'description' => 'Surat Keputusan Dekan tentang Penyelenggaraan Perwalian/Dosen Wali Fakultas Ilmu Komputer Universitas Subang.',
                'letter_code' => 'KEP/PERWALIAN/FIK-US',
                'order' => 28,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('letter_types')
            ->where('code', 'sk_dekan_dosen_wali')
            ->update([
                'name' => 'SK Dekan Penetapan Dosen Wali Akademik',
                'letter_code' => 'D/FIK-US',
                'order' => 0,
                'updated_at' => now(),
            ]);
    }
};
