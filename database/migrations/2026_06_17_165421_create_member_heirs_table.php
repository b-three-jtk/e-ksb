<?php

use App\Enums\HeirEnum;
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
        Schema::create('member_heirs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('member_id');
            $table->string('heir_nik', 16);

            $table->enum('relationship', array_column(HeirEnum::cases(), 'value'));

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->foreign('heir_nik')->references('heir_nik')->on('heirs')->onDelete('cascade');

            $table->unique(['member_id', 'heir_nik']);

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
