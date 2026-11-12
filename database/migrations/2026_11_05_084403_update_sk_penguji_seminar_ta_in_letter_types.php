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
            ->where('code', 'sk_dekan_penguji_sidang_ta')
            ->update([
                'name' => 'SK Pelaksanaan Pengujian Seminar Tugas Akhir',
                'description' => 'Surat Keputusan Dekan tentang Pelaksanaan Kegiatan Pengujian Seminar Tugas Akhir/Skripsi Fakultas Ilmu Komputer Universitas Subang.',
                'letter_code' => 'D/FIK-US',
                'order' => 30,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('letter_types')
            ->where('code', 'sk_dekan_penguji_sidang_ta')
            ->update([
                'name' => 'SK Dekan Dewan Penguji Sidang Tugas Akhir',
                'order' => 0,
                'updated_at' => now(),
            ]);
    }
};
