<?php

use App\Enums\SavingTypeEnum;
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
        Schema::create('akun_simpanan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_akun_simpanan', 20)->unique();
            $table->decimal('saldo', 15, 2)->default(0);
            $table->enum('jenis_simpanan', array_column(SavingTypeEnum::cases(), 'value'));
            $table->unsignedBigInteger('anggota_id')->nullable();

            $table->foreign('anggota_id')->references('id')->on('anggota')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun_simpanan');
    }
};
