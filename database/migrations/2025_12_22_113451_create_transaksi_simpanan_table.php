<?php

use App\Enums\PaymentMethodsEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi_simpanan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_transaksi_simpanan', 20)->unique();
            $table->decimal('nominal_simpanan', 15, 2);
            $table->decimal('saldo_setelah_transaksi', 15, 2);
            $table->enum('tipe_transaksi', array_column(TransactionTypeEnum::cases(), 'value'));
            $table->enum('metode_pembayaran_simpanan', array_column(PaymentMethodsEnum::cases(), 'value'));
            $table->text('deskripsi_simpanan')->nullable();
            $table->datetime('tgl_transaksi');
            $table->string('struk_simpanan')->nullable();

            $table->foreignUuid('updated_by')->nullable()->constrained('pengguna')->onDelete('set null');
            $table->foreignUuid('akun_simpanan_id')->nullable()->constrained('akun_simpanan')->onDelete('set null');
            $table->string('no_rekening')->nullable();
            $table->foreign('no_rekening')->references('no_rekening')->on('rekening_anggota')->onDelete('set null');
            $table->foreignId('poin_id')->nullable()->constrained('poin')->onDelete('set null');
            $table->timestamps();

            $table->index('kode_transaksi_simpanan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_simpanan');
    }
};
