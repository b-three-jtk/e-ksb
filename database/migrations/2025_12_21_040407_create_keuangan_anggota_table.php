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
        Schema::create('keuangan_anggota', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_id');
            $table->decimal('jml_gaji_pokok', 15, 2);
            $table->decimal('jml_penghasilan_usaha', 15, 2);
            $table->decimal('jml_penghasilan_pasangan', 15, 2);
            $table->decimal('jml_penghasilan_lainnya', 15, 2);
            $table->decimal('jml_biaya_hidup_keluarga', 15, 2);
            $table->decimal('jml_biaya_pendidikan', 15, 2);
            $table->decimal('jml_cicilan', 15, 2);
            $table->decimal('jml_biaya_lainnya', 15, 2);

            $table->foreign('anggota_id')->references('id')->on('anggota')->onDelete('cascade');
            $table->unique('anggota_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keuangan_anggota');
    }
};
