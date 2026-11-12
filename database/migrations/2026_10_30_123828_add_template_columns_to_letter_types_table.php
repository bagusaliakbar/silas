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
        Schema::table('letter_types', function (Blueprint $table) {
            $table->text('opening_template')->nullable()->after('letter_code');
            $table->text('content_template')->nullable()->after('opening_template');
            $table->text('closing_template')->nullable()->after('content_template');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('letter_types', function (Blueprint $table) {
            $table->dropColumn(['opening_template', 'content_template', 'closing_template']);
        });
    }
};
