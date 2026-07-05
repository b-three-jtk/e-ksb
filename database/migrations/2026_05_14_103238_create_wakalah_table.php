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
        Schema::create('wakalah', function (Blueprint $table) {
            $table->id();
            $table->date('tgl_akad');
            $table->string('dokumen_akad')->nullable();
            $table->uuid('pembiayaan_id');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('pembiayaan_id')->references('id')->on('pembiayaan')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('pengguna')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wakalahs');
    }
};
