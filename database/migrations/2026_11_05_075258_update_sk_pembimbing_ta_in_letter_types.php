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
            ->where('code', 'sk_dekan_pembimbing_ta')
            ->update([
                'name' => 'SK Dosen Pembimbing Tugas Akhir',
                'description' => 'Surat Keputusan Dekan tentang Dosen Pembimbing Tugas Akhir Fakultas Ilmu Komputer Universitas Subang.',
                'letter_code' => 'D/FIK-US',
                'order' => 27,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('letter_types')
            ->where('code', 'sk_dekan_pembimbing_ta')
            ->update([
                'name' => 'SK Dekan Dosen Pembimbing Skripsi / Tugas Akhir',
                'order' => 0,
                'updated_at' => now(),
            ]);
    }
};
