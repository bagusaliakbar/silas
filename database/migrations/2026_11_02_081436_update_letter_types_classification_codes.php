<?php

use App\Models\LetterType;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mappings = [
            'tugas_mengajar' => 'PD.1.1/FIK-US',
            'tugas_pembimbing_mbkm' => 'PD.1.1/FIK-US',
            'tugas_pembimbing_ta' => 'PD.1.1/FIK-US',
            'undangan_rapat_akademik' => 'PD.1.1/FIK-US',

            'keterangan_kuliah_non_pns' => 'PD.1.2/FIK-US',
            'keterangan_kuliah_pns' => 'PD.1.2/FIK-US',
            'pengantar_magang' => 'PD.1.2/FIK-US',
            'pengantar_pkl' => 'PD.1.2/FIK-US',
            'observasi_matkul' => 'PD.1.2/FIK-US',
            'perbaikan_nilai' => 'PD.1.2/FIK-US',

            'ujian_susulan' => 'PD.1.3/FIK-US',
            'izin_mengikuti_ujian' => 'PD.1.3/FIK-US',
            'izin_seminar_up' => 'PD.1.3/FIK-US',
            'izin_sidang_ta' => 'PD.1.3/FIK-US',
            'keterangan_yudisium' => 'PD.1.3/FIK-US',

            'izin_penelitian_skripsi' => 'PD.1.4/FIK-US',
        ];

        foreach ($mappings as $code => $letterCode) {
            LetterType::where('code', $code)->update(['letter_code' => $letterCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse
    }
};
