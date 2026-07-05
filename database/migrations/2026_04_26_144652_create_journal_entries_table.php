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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('no_ref_akun');
            $table->enum('position', ['Debit', 'Credit']);
            $table->decimal('nominal', 15, 2);
            $table->date('tgl_transaksi');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('no_ref_akun')->references('no_ref_akun')->on('akun')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('pengguna')->onDelete('set null');
            $table->index('no_ref_akun');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
