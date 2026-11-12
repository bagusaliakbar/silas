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
            ->where('code', 'sk_dekan_yudisium')
            ->update([
                'name' => 'SK Kelulusan / Yudisium',
                'description' => 'Surat Keputusan Dekan tentang Kelulusan Ujian Sidang Sarjana dan Peserta Yudisium Fakultas Ilmu Komputer Universitas Subang.',
                'letter_code' => 'D/FIK-US',
                'order' => 29,
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('letter_types')
            ->where('code', 'sk_dekan_yudisium')
            ->update([
                'name' => 'SK Dekan Kelulusan dan Yudisium Sarjana',
                'order' => 0,
                'updated_at' => now(),
            ]);
    }
};
