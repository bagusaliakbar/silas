<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('email');
        });

        // Set default username for existing admin/pejabat users
        DB::table('users')->where('email', 'admin@esign.test')->update(['username' => 'admin']);
        DB::table('users')->where('email', 'kaprodi.si@unsub.ac.id')->update(['username' => 'kaprodi']);
        DB::table('users')->where('email', 'dekan.fik@unsub.ac.id')->update(['username' => 'dekan']);
        DB::table('users')->where('email', 'wadek.fik@unsub.ac.id')->update(['username' => 'wadek']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }
};
