<?php

use App\Enums\PaymentMethodsEnum;
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
        Schema::create('pembayaran_angsuran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_transaksi_pembayaran', 20)->unique();
            $table->decimal('jumlah_angsuran_dibayar', 15, 2);
            $table->enum('metode_pembayaran', array_column(PaymentMethodsEnum::cases(), 'value'));
            $table->boolean('is_pelunasan_lebih_cepat')->default(false);
            $table->datetime('tgl_pembayaran');
            $table->string('struk_pembayaran')->nullable();
            $table->string('bukti_pembayaran')->nullable();
            $table->decimal('pokok_dibayar', 15, 2)
                  ->nullable();
            $table->decimal('margin_dibayar', 15, 2)
                  ->nullable();

            $table->string('no_rekening')->nullable();
            $table->foreign('no_rekening')->references('no_rekening')->on('rekening_anggota')->onDelete('set null');
            $table->foreignUuid('angsuran_id')->nullable()->references('id')->on('angsuran')->onDelete('set null');
            $table->foreignUuid('updated_by')->constrained('pengguna')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
