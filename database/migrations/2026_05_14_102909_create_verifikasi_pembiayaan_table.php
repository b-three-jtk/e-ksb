<?php

use App\Enums\FinancingReqStatusEnum;
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
        Schema::create('verifikasi_pembiayaan', function (Blueprint $table) {
            $table->id();
            $table->uuid('pembiayaan_id');
            $table->enum('keputusan_akhir', array_column(FinancingReqStatusEnum::cases(), 'value'));
            $table->text('catatan')->nullable();
            $table->uuid('diverifikasi_oleh')->nullable();
            $table->dateTime('diverifikasi_pada')->nullable();

            $table->foreign('pembiayaan_id')->references('id')->on('pembiayaan')->onDelete('cascade');
            $table->foreign('diverifikasi_oleh')->references('id')->on('pengguna')->onDelete('set null');
            $table->index('pembiayaan_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verifikasi_pembiayaan');
    }
};
