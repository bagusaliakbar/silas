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
            ['code' => 'rekomendasi_luar_prodi'],
            [
                'name' => 'Surat Rekomendasi Kegiatan Pembelajaran di Luar Program Studi',
                'description' => 'Surat Rekomendasi Fakultas / Program Studi untuk mahasiswa yang mengikuti program pembelajaran di luar prodi / MBKM / Coding Camp / Studi Independen dengan komitmen dukungan dan konversi SKS.',
                'letter_code' => 'PD.1.6/FIK-US',
                'opening_template' => "Yang bertanda tangan di bawah ini:\n\nNama : {pejabat_nama}\nJabatan : {pejabat_jabatan}\nProgram Studi : {pejabat_prodi}\nFakultas : {pejabat_fakultas}",
                'content_template' => "Dengan ini memberikan rekomendasi kepada:\n\nNama : {student_name}\nNPM : {npm}\nProgram Studi : {study_program}\nFakultas : {fakultas_mahasiswa}\nSemester : {semester_text}\nTahun Akademik : {academic_year}\n\nUntuk menjadi peserta di {program_name} pada {activity_period} dengan pertimbangan bahwa mahasiswa tersebut sanggup mengikuti {program_name} secara penuh dan bertanggung jawab hingga akhir program selesai.\n\nSebagai bentuk dukungan, kami selaku pihak perwakilan kampus menyatakan kesediaan untuk hal berikut:\n1. Memberikan dukungan dan bertanggung jawab atas mahasiswa selama periode pelaksanaan {program_name}.\n2. Memberikan pengakuan keikutsertaan siswa/mahasiswa di {program_name} melalui konversi SKS atau hal lainnya yang dapat diakui dan menunjang kegiatan akademik di kampus.\n3. Mampu menjadi jembatan untuk berkoordinasi antara Tim {program_name} dan mahasiswa apabila terjadi masalah/kendala di kemudian hari.",
                'closing_template' => 'Demikian surat rekomendasi ini kami sampaikan untuk dipergunakan sebagaimana mestinya.',
                'required_fields' => json_encode(['program_name', 'activity_period', 'pejabat_prodi', 'pejabat_fakultas']),
                'template_view' => null,
                'is_active' => 1,
                'order' => 24,
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
        DB::table('letter_types')->where('code', 'rekomendasi_luar_prodi')->delete();
    }
};
