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
        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('reference_number')->nullable(); // Nomor Surat dari pengirim
            $table->string('sender'); // Asal / Instansi Pengirim
            $table->string('subject'); // Perihal Surat
            $table->date('letter_date'); // Tanggal pada surat
            $table->date('received_date'); // Tanggal surat diterima di fakultas
            $table->string('category')->default('Umum'); // Kategori: Dinas, Undangan, Kerjasama, dll
            $table->string('status')->default('received'); // Status: received, disposed, archived
            $table->text('description')->nullable(); // Ringkasan / Keterangan isi surat
            $table->string('file_path')->nullable(); // Path berkas PDF/dokumen lampiran
            $table->string('file_name')->nullable(); // Nama asli berkas
            $table->unsignedBigInteger('file_size')->nullable(); // Ukuran berkas (bytes)
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incoming_letters');
    }
};
