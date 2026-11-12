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
        DB::table('letter_types')->updateOrInsert(
            ['code' => 'sk_dekan_pengujian_sidang_skripsi'],
            [
                'name' => 'SK Pelaksanaan Pengujian Sidang Tugas Akhir',
                'description' => 'Surat Keputusan Dekan tentang Pelaksanaan Kegiatan Pengujian Sidang Tugas Akhir/Skripsi Fakultas Ilmu Komputer Universitas Subang.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => '',
                'content_template' => '',
                'closing_template' => '',
                'required_fields' => json_encode(['academic_year', 'semester_type', 'wave_info', 'exam_schedule_date', 'exam_room', 'sidang_matrix']),
                'template_view' => null,
                'is_active' => 1,
                'order' => 31,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('letter_types')->where('code', 'sk_dekan_pengujian_sidang_skripsi')->delete();
    }
};
