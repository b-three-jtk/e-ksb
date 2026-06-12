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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id');
            $table->enum('gender', array_column(GenderEnum::cases(), 'value'))->nullable();
            $table->string('birth_place', 150)->nullable();
            $table->date('birth_date')->nullable();
            $table->text('domicile_address')->nullable();
            $table->text('residential_address')->nullable();
            $table->enum('marital_status', array_column(MaritalStatusEnum::cases(), 'value'))->nullable();
            $table->enum('last_education', array_column(EducationEnum::cases(), 'value'))->nullable();
            $table->integer('dependents')->nullable();
            $table->enum('status', array_column(MemberStatusEnum::cases(), 'value'))->default('Menunggu Pembayaran');
            $table->date('resignation_date')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
