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
        Schema::create('signature_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('signer_name');
            $table->string('signer_email');
            $table->string('role')->default('signer');
            $table->unsignedInteger('page_number')->default(1);
            $table->float('pos_x');
            $table->float('pos_y');
            $table->float('width')->default(160);
            $table->float('height')->default(65);
            $table->string('field_type')->default('signature');
            $table->string('signature_type')->nullable();
            $table->longText('signature_data')->nullable();
            $table->string('signature_image_path')->nullable();
            $table->foreignId('digital_certificate_id')->nullable()->constrained('digital_certificates')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->dateTime('signed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signature_fields');
    }
};
