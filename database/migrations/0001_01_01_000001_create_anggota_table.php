<?php

use App\Enums\EducationEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\MemberStatusEnum;
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
        Schema::create('anggota', function (Blueprint $table) {
            $table->id();
            $table->uuid('pengguna_id');
            $table->uuid('pj_anggota_id')->nullable();
            $table->enum('jenis_kelamin', array_column(GenderEnum::cases(), 'value'))->nullable();
            $table->string('tempat_lahir', 150)->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->text('alamat_domisili')->nullable();
            $table->text('alamat_ktp')->nullable();
            $table->enum('status_pernikahan', array_column(MaritalStatusEnum::cases(), 'value'))->nullable();
            $table->enum('pendidikan_terakhir', array_column(EducationEnum::cases(), 'value'))->nullable();
            $table->integer('jml_tanggungan')->nullable();
            $table->enum('status', array_column(MemberStatusEnum::cases(), 'value'))->default('Menunggu Pembayaran');
            $table->date('tgl_pengunduran_diri')->nullable();
            $table->timestamps();

            $table->foreign('pengguna_id')->references('id')->on('pengguna')->onDelete('cascade');
            $table->foreign('pj_anggota_id')->references('id')->on('pengguna')->onDelete('set null');

            $table->index('pengguna_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('anggota');
    }
};
