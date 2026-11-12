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
            ['code' => 'rekomendasi_pinjaman_bjb'],
            [
                'name' => 'Surat Rekomendasi Pinjaman BJB',
                'description' => 'Surat Rekomendasi Dekan kepada Dosen/Pegawai/Staf untuk pengajuan pinjaman/kredit Bank BJB dengan pemotongan gaji langsung.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => "Yang bertandatangan dibawah ini :\n\nNama : {dekan_name}\nNIK : {dekan_nik}\nJabatan : {dekan_position}",
                'content_template' => "Dengan ini merekomendasikan kepada :\n\nNama : {employee_name}\nNIK : {employee_nik}\nJabatan : {employee_position}\n\nAdapun sebagai staf Fakultas Ilmu Komputer Universitas Subang, dan oleh karenanya dapat dipertimbangkan untuk diberikan kredit dari Bank BJB untuk {loan_purpose}.\n\nUntuk kelancaran pengambilan kredit tersebut, maka kami mendukung/tidak berkeberatan untuk dipotong langsung dari gaji yang bersangkutan oleh Bank BJB.",
                'closing_template' => 'Demikian surat rekomendasi ini di buat, untuk dipergunakan sebagaimana mestinya.',
                'required_fields' => json_encode(['employee_name', 'employee_nik', 'employee_position', 'loan_purpose', 'bank_name', 'dekan_name', 'dekan_nik', 'dekan_position']),
                'template_view' => null,
                'is_active' => 1,
                'order' => 23,
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
        DB::table('letter_types')->where('code', 'rekomendasi_pinjaman_bjb')->delete();
    }
};
