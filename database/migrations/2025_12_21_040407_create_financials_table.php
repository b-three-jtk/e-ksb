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
        Schema::create('financials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->decimal('gaji_pokok_amount', 15, 2);
            $table->decimal('penghasilan_usaha_amount', 15, 2);
            $table->decimal('penghasilan_pasangan_amount', 15, 2);
            $table->decimal('penghasilan_lainnya_amount', 15, 2);
            $table->decimal('biaya_hidup_keluarga_amount', 15, 2);
            $table->decimal('biaya_pendidikan_amount', 15, 2);
            $table->decimal('jumlah_cicilan_amount', 15, 2);
            $table->decimal('jumlah_biaya_lainnya_amount', 15, 2);

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->unique('member_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financials');
    }
};
