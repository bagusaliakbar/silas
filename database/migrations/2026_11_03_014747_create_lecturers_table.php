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
        Schema::create('lecturers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('nidn')->nullable()->index();
            $table->string('nip')->nullable();
            $table->string('functional_position')->nullable(); // Asisten Ahli, Lektor, Lektor Kepala, Guru Besar, Tenaga Pengajar
            $table->string('academic_rank')->nullable(); // Penata Muda / III a, dll.
            $table->string('study_program')->nullable(); // Sistem Informasi, Teknik Informatika, dll.
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturers');
    }
};
