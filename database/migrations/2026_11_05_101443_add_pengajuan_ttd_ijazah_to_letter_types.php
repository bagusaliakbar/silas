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
            ['code' => 'pengajuan_ttd_ijazah'],
            [
                'name' => 'Surat Pengajuan Penandatanganan Ijazah',
                'description' => 'Surat Pengantar Pengajuan Penandatanganan Ijazah Mahasiswa Fakultas Ilmu Komputer kepada Rektor Universitas Subang.',
                'letter_code' => 'PD.1.1/FIK-US',
                'opening_template' => 'Bersama surat ini kami sampaikan bahwa Fakultas Ilmu Komputer Universitas Subang telah melaksanakan sidang komprehensif pada:',
                'content_template' => '',
                'closing_template' => 'Demikian surat pengajuan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.',
                'required_fields' => json_encode(['lampiran_count', 'subject', 'target_recipient', 'target_city', 'session_summary_table']),
                'template_view' => null,
                'is_active' => 1,
                'order' => 21,
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
        DB::table('letter_types')->where('code', 'pengajuan_ttd_ijazah')->delete();
    }
};
