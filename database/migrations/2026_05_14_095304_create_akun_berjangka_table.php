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
        Schema::create('akun_berjangka', function (Blueprint $table) {
            $table->id();
            $table->integer('tenor');
            $table->string('tujuan');
            $table->uuid('akun_simpanan_id');

            $table->foreign('akun_simpanan_id')->references('id')->on('akun_simpanan')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun_berjangka');
    }
};
