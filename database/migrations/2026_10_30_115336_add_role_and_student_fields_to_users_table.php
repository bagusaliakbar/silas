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
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('mahasiswa')->after('email');
            $table->string('npm')->nullable()->after('role');
            $table->string('study_program')->nullable()->default('Sistem Informasi')->after('npm');
            $table->string('phone')->nullable()->after('study_program');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'npm', 'study_program', 'phone']);
        });
    }
};
