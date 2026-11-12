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
            ['code' => 'rekomendasi_beasiswa'],
            [
                'name' => 'Surat Rekomendasi Beasiswa',
                'description' => 'Surat Rekomendasi Ketua Program Studi / Dekan bagi mahasiswa untuk mendapatkan beasiswa dari yayasan, lembaga, atau instansi penyedia beasiswa.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => "Yang bertanda tangan di bawah ini:\n\nNama : {pejabat_nama}\nJabatan : {pejabat_jabatan}\nFakultas : {pejabat_fakultas}",
                'content_template' => 'Merekomendasikan kepada mahasiswa atas nama {student_name} dengan nomor pokok mahasiswa {npm} untuk mendapatkan {scholarship_name} oleh {scholarship_provider}.',
                'closing_template' => 'Demikian surat rekomendasi ini dibuat untuk dipergunakan sebagaimana mestinya. Atas perhatiannya, kami ucapkan terima kasih',
                'required_fields' => json_encode(['scholarship_name', 'scholarship_provider']),
                'template_view' => null,
                'is_active' => 1,
                'order' => 19,
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
        DB::table('letter_types')->where('code', 'rekomendasi_beasiswa')->delete();
    }
};
