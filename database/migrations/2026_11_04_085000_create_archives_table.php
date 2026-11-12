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
        Schema::create('archive_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('letter_direction')->default('both'); // 'masuk', 'keluar', 'both'
            $table->text('description')->nullable();
            $table->string('color')->default('blue');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('archives', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title'); // Judul Arsip
            $table->string('reference_number')->nullable(); // Nomor Surat / Dokumen
            $table->date('letter_date'); // Tanggal Dokumen / Surat
            $table->enum('letter_type', ['masuk', 'keluar', 'lainnya'])->default('masuk'); // Jenis: Surat Masuk / Keluar / Lainnya
            $table->foreignId('archive_category_id')->nullable()->constrained('archive_categories')->nullOnDelete();
            
            // Relasi opsional jika diarsipkan dari sistem
            $table->foreignId('incoming_letter_id')->nullable()->constrained('incoming_letters')->nullOnDelete();
            $table->foreignId('letter_request_id')->nullable()->constrained('letter_requests')->nullOnDelete();
            
            // Pengirim & Penerima
            $table->string('sender')->nullable(); // Asal / Pengirim
            $table->string('recipient')->nullable(); // Penerima / Tujuan
            
            // Tautan Berkas / Google Form / Google Drive
            $table->text('google_form_url')->nullable(); // URL bukti Google Form respon/upload
            $table->text('drive_url')->nullable(); // Tautan Google Drive berkas
            $table->string('file_path')->nullable(); // Jika diunggah langsung berkas PDF
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            
            $table->text('description')->nullable(); // Catatan / Ringkasan isi berkas
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archives');
        Schema::dropIfExists('archive_categories');
    }
};

