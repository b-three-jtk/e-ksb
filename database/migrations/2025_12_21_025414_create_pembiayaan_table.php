<?php

use App\Enums\FinancingPaymentMethodEnum;
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
        Schema::create('pembiayaan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_pembiayaan')->unique();
            $table->decimal('uang_muka', 15, 2)->nullable();
            $table->enum('status', array_column(FinancingReqStatusEnum::cases(), 'value'))->default(FinancingReqStatusEnum::WAITING_DOCUMENTS->value);
            $table->enum('metode_pembayaran', array_column(FinancingPaymentMethodEnum::cases(), 'value'))->nullable();
            $table->decimal('harga_perolehan', 15, 2)->nullable();
            $table->decimal('margin_keuntungan', 15, 2)->nullable();
            $table->string('dokumen_akad')->nullable();
            $table->date('tgl_permohonan')->nullable();
            $table->date('tgl_akad')->nullable();
            $table->date('tgl_lunas')->nullable();
            $table->integer('tenor')->nullable();
            $table->decimal('harga_perkiraan', 15, 2)->nullable();

            // set null so that if the anggota is deleted, the pembiayaan record will not be deleted but the id fk will be set to null
            $table->foreignUuid('updated_by')->nullable()->constrained('pengguna')->onDelete('set null');
            $table->timestamps();

            $table->foreignId('anggota_id')->references('id')->on('anggota')->onDelete('set null');
            $table->index('kode_pembiayaan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pembiayaan');
    }
};
