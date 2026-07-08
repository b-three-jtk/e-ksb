<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jaminan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('pembiayaan_id')->constrained('pembiayaan')->onDelete('cascade');
            $table->string('jenis_jaminan');
            $table->string('nama_pemilik');
            $table->string('lokasi_kondisi_jaminan')->nullable();
            $table->decimal('nilai_perkiraan_pasar', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jaminan');
    }
};
