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
        Schema::create('letter_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('letter_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained()->nullOnDelete();
            $table->string('letter_number')->nullable();
            $table->string('student_name');
            $table->string('npm');
            $table->string('study_program')->default('Sistem Informasi');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->text('address')->nullable();
            $table->integer('semester')->default(1);
            $table->string('academic_year')->default('2026/2027');
            $table->text('purpose')->nullable();
            $table->json('extra_data')->nullable();
            $table->string('status')->default('submitted');
            $table->text('rejection_reason')->nullable();
            $table->string('signer_name')->default('BAGUS ALI AKBAR, S.Si., M.Kom');
            $table->string('signer_title')->default('Ka. Program Studi');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('letter_requests');
    }
};
