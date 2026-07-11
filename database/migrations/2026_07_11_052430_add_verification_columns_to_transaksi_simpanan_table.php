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
        Schema::table('transaksi_simpanan', function (Blueprint $table) {
            $table->enum('status', ['Menunggu Verifikasi', 'Diverifikasi', 'Ditolak'])->default('Diverifikasi');
            $table->foreignUuid('verified_by')->nullable()->constrained('pengguna')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_simpanan', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['status', 'verified_by', 'verified_at']);
        });
    }
};
