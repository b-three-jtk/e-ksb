<?php

use App\Enums\HeirEnum;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('heirs', function (Blueprint $table) {
            $table->string('heir_nik', 16)->primary();
            $table->string('heir_name');
            $table->enum('relationship', array_column(HeirEnum::cases(), 'value'));
            $table->string('heir_contact', 20)->nullable();
            $table->unsignedBigInteger('member_id');

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heirs');
    }
};
