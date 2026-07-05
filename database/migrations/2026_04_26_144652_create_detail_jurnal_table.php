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
        Schema::create('detail_jurnal', function (Blueprint $table) {
            $table->id();
            $table->string('no_ref_akun');
            $table->enum('posisi_akun', ['Debit', 'Credit']);
            $table->decimal('nominal', 15, 2);
            $table->uuid('updated_by')->nullable();
            $table->uuid('jurnal_id')->index();
            $table->timestamps();

            $table->foreign('jurnal_id')->references('id')->on('jurnal')->onDelete('cascade');
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
        Schema::dropIfExists('detail_jurnal');
    }
};
