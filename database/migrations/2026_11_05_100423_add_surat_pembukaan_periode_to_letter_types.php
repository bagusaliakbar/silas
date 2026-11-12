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
            ['code' => 'pembukaan_periode'],
            [
                'name' => 'Surat Pengantar Pembukaan Periode',
                'description' => 'Surat Pengantar Permohonan Pembukaan Periode Tipe 2 melalui Feeder PDDIKTI untuk perbaikan data mahasiswa / PIN.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => "Dengan Hormat,\n\nSehubungan dengan kebutuhan perbaikan data PIN mahasiswa yang akan lulus, dengan ini kami mengajukan permohonan Pembukaan Periode Tipe 2 melalui Feeder PDDIKTI untuk memperbaiki data pada Program Studi Sistem Informasi (57201). Adapun data yang kami ajukan sebagaimana data terlampir.",
                'content_template' => '',
                'closing_template' => 'Demikian surat ini kami buat untuk dapat ditindaklanjuti. Atas perhatiannya kami ucapkan terimakasih.',
                'required_fields' => json_encode(['lampiran_count', 'subject', 'target_recipient', 'target_city', 'purpose_description', 'feeder_type', 'study_program_info']),
                'template_view' => null,
                'is_active' => 1,
                'order' => 20,
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
        DB::table('letter_types')->where('code', 'pembukaan_periode')->delete();
    }
};
