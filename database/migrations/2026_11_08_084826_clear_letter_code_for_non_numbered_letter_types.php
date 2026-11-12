<?php

use App\Models\LetterType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('letter_types', function (Blueprint $table) {
            $table->string('letter_code')->nullable()->default(null)->change();
        });

        // Fakta Integritas dan Surat Pernyataan Kesanggupan Mengajar adalah format dokumen tanpa nomor surat / kode klasifikasi
        LetterType::whereIn('code', ['pakta_integritas', 'kesanggupan_mengajar'])->update([
            'letter_code' => null,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        LetterType::where('code', 'pakta_integritas')->update(['letter_code' => 'PD.1.2/FIK-US']);
        LetterType::where('code', 'kesanggupan_mengajar')->update(['letter_code' => 'PD.1.2/FIK-US']);

        Schema::table('letter_types', function (Blueprint $table) {
            $table->string('letter_code')->default('PD.1.2/FIK-US')->change();
        });
    }
};
