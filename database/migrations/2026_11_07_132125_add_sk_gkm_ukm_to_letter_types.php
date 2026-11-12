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
            ['code' => 'sk_dekan_gkm_ukm'],
            [
                'name' => 'SK Pengangkatan GKM dan UKM',
                'description' => 'Surat Keputusan Dekan tentang Penunjukan dan Pengangkatan Ketua Gugus Kendali Mutu (GKM) pada Fakultas dan Unit Kendali Mutu (UKM) pada Program Studi Sistem Informasi Universitas Subang.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => '',
                'content_template' => '',
                'closing_template' => '',
                'required_fields' => json_encode([
                    'about_title',
                    'academic_year',
                    'gkm_chairman_name',
                    'ukm_chairman_name',
                    'study_program',
                    'meeting_date_text',
                    'decision_city',
                    'decision_date_text',
                    'dekan_name',
                    'dekan_nik',
                ]),
                'template_view' => null,
                'is_active' => 1,
                'order' => 33,
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
        DB::table('letter_types')->where('code', 'sk_dekan_gkm_ukm')->delete();
    }
};
