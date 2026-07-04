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
        Schema::table('members', function (Blueprint $table) {
            if (!Schema::hasColumn('members', 'pj_anggota_id')) {
                $table->uuid('pj_anggota_id')->nullable()->after('pengguna_id');
                $table->foreign('pj_anggota_id')->references('id')->on('pengguna')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (Schema::hasColumn('members', 'pj_anggota_id')) {
                $table->dropForeign(['pj_anggota_id']);
                $table->dropColumn('pj_anggota_id');
            }
        });
    }
};
