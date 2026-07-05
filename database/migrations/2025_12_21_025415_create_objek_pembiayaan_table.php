<?php

use App\Enums\ConditionEnum;
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
        Schema::create('objek_pembiayaan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            $table->text('spesifikasi_barang')->nullable();
            $table->integer('kuantitas');
            $table->enum('kondisi_produk', array_column(ConditionEnum::cases(), 'value'));
            $table->decimal('harga_beli_per_unit', 15, 2)->nullable();
            $table->string('struk_pembelian')->nullable();

            $table->foreignId('jenis_barang_id')->nullable()->references('id')->on('jenis_barang')->onDelete('set null');
            $table->foreignId('pemasok_id')->nullable()->references('id')->on('pemasok')->onDelete('set null');
            $table->foreignUuid('pembiayaan_id')->references('id')->on('pembiayaan')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('objek_pembiayaan');
    }
};
