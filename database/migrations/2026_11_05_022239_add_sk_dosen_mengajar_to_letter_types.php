<?php

use App\Models\LetterType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        LetterType::updateOrCreate(
            ['code' => 'sk_dekan_dosen_mengajar'],
            [
                'name' => 'SK Dosen Mengajar',
                'description' => 'Surat Keputusan Dekan tentang Penunjukan Dosen Pengajar Semester Ganjil/Genap beserta lampiran beban mengajar mata kuliah.',
                'letter_code' => 'D/FIK-US',
                'opening_template' => '',
                'content_template' => '',
                'closing_template' => '',
                'required_fields' => ['academic_year', 'semester_type', 'study_program', 'teaching_matrix'],
                'is_active' => true,
                'order' => 26,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        LetterType::where('code', 'sk_dekan_dosen_mengajar')->delete();
    }
};
