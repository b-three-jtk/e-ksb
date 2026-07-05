<?php

use App\Enums\AhliWarisEnum;
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
        Schema::create('anggota_ahli_waris', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('anggota_id');
            $table->string('nik_ahli_waris', 16);

            $table->enum('hubungan', array_column(AhliWarisEnum::cases(), 'value'));

            $table->foreign('anggota_id')->references('id')->on('anggota')->onDelete('cascade');
            $table->foreign('nik_ahli_waris')->references('nik_ahli_waris')->on('ahli_waris')->onDelete('cascade');

            $table->unique(['anggota_id', 'nik_ahli_waris']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_heir');
    }
};
