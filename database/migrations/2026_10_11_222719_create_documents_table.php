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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('original_filename');
            $table->string('file_path');
            $table->string('signed_file_path')->nullable();
            $table->unsignedBigInteger('file_size');
            $table->unsignedInteger('total_pages')->default(1);
            $table->string('sha256_original', 64);
            $table->string('sha256_signed', 64)->nullable();
            $table->string('status')->default('draft');
            $table->string('signing_method')->default('visual_and_qr');
            $table->string('psre_provider')->nullable();
            $table->foreignId('digital_certificate_id')->nullable()->constrained('digital_certificates')->nullOnDelete();
            $table->string('verification_token', 64)->unique();
            $table->json('metadata')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
