<?php

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
        Schema::create('letter_classifications', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g. PD.1.1/FIK-US
            $table->string('name'); // e.g. Hubungan Dengan Dosen
            $table->string('category')->nullable(); // e.g. Akademik / Kemahasiswaan / Umum
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_classifications');
    }
};
