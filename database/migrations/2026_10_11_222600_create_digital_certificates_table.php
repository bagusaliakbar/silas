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
        Schema::create('digital_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('organization')->nullable();
            $table->string('organizational_unit')->nullable();
            $table->string('email')->nullable();
            $table->string('country', 2)->default('ID');
            $table->string('certificate_path');
            $table->string('private_key_path');
            $table->string('p12_path')->nullable();
            $table->text('passphrase')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('issuer')->nullable();
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('digital_certificates');
    }
};
