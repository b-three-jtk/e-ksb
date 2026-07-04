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
        Schema::table('saving_transactions', function (Blueprint $table) {
            $table->string('saving_transaction_code', 20)->change();
        });

        Schema::table('akun_simpanan', function (Blueprint $table) {
            $table->string('kode_akun_simpanan', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_transactions', function (Blueprint $table) {
            $table->string('saving_transaction_code', 10)->change();
        });

        Schema::table('akun_simpanan', function (Blueprint $table) {
            $table->string('kode_akun_simpanan', 10)->change();
        });
    }
};
