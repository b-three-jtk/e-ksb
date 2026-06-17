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
            $table->dropForeign(['member_id']);
            $table->dropColumn(['member_id', 'relationship']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('heirs', function (Blueprint $table) {
            $table->unsignedBigInteger('member_id')->after('heir_nik')->nullable();
            $table->enum('relationship', array_column(\App\Enums\HeirEnum::cases(), 'value'))->after('heir_name');

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }
};
