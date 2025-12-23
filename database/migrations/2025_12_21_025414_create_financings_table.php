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
        Schema::create('financings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('product_type');
            $table->string('brand');
            $table->string('color');
            $table->enum('condition', ['Bekas', 'Baru']);
            $table->text('description');
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('qty');
            $table->decimal('profit', 15, 2)->nullable();
            $table->enum('status', ['Belum Ditinjau', 'Disetujui', 'Disetujui Dengan Catatan', 'Ditolak', 'Menunggu Kelengkapan Dokumen', 'Barang Diterima']);
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financings');
    }
};
