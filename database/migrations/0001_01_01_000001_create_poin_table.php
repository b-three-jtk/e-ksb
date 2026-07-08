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
        Schema::create('poin', function (Blueprint $table) {
            $table->id();
            $table->integer('jml_perolehan');
            $table->text('deskripsi');
            $table->date('periode_kalkulasi')->nullable();
            $table->decimal('sisa_tabungan_snapshot', 15, 2)->default(0);
            $table->foreignUuid('pengguna_id')->nullable()->constrained('pengguna')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('poin');
    }
};
