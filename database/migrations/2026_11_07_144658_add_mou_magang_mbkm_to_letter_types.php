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
            ['code' => 'mou_magang_mbkm'],
            [
                'name' => 'Nota Kesepahaman (MoU) Magang MBKM',
                'description' => 'Nota Kesepahaman antara Institusi/Perusahaan Mitra dengan Fakultas Ilmu Komputer Universitas Subang tentang Penyelenggaraan Program Magang Bersertifikat - MBKM.',
                'letter_code' => 'MOU/FIK-US',
                'opening_template' => '',
                'content_template' => '',
                'closing_template' => '',
                'required_fields' => json_encode([
                    'partner_name',
                    'partner_address',
                    'partner_business_field',
                    'partner_official_name',
                    'partner_official_position',
                    'mou_title',
                    'partner_mou_number',
                    'faculty_mou_number',
                    'signing_city',
                    'signing_date',
                    'program_duration_months',
                    'agreement_duration_years',
                    'confidentiality_duration_years',
                ]),
                'template_view' => null,
                'is_active' => 1,
                'order' => 50,
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
        DB::table('letter_types')->where('code', 'mou_magang_mbkm')->delete();
    }
};
