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
        Schema::table('heirs', function (Blueprint $table) {
            $table->dropForeign(['anggota_id']);
            $table->dropColumn(['anggota_id', 'relationship']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('heirs', function (Blueprint $table) {
            $table->unsignedBigInteger('anggota_id')->after('heir_nik')->nullable();
            $table->enum('relationship', array_column(\App\Enums\HeirEnum::cases(), 'value'))->after('heir_name');

            $table->foreign('anggota_id')->references('id')->on('anggota')->onDelete('cascade');
        });
    }
};
