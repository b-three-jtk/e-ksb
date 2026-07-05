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
        Schema::create('rekening_anggota', function (Blueprint $table) {
            $table->string('no_rekening', 20)->primary();
            $table->string('nama_bank');
            $table->string('atas_nama');
            $table->unsignedBigInteger('anggota_id');

            $table->foreign('anggota_id')->references('id')->on('anggota')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
