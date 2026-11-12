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
            ['code' => 'sk_dekan_pengunduran_diri'],
            [
                'name' => 'SK Pengunduran Diri Mahasiswa',
                'description' => 'Surat Keputusan Dekan tentang Pengunduran Diri Mahasiswa di Lingkungan Fakultas Ilmu Komputer Universitas Subang.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => '',
                'content_template' => '',
                'closing_template' => '',
                'required_fields' => json_encode(['academic_year', 'semester_type', 'study_program', 'resignation_matrix']),
                'template_view' => null,
                'is_active' => 1,
                'order' => 32,
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
        DB::table('letter_types')->where('code', 'sk_dekan_pengunduran_diri')->delete();
    }
};

