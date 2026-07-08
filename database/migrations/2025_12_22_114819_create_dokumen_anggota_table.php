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
        Schema::create('dokumen_anggota', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nama_dokumen');
            $table->string('lampiran_dokumen');
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
        Schema::dropIfExists('user_docs');
    }
};
