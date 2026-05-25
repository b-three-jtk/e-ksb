<?php

use App\Enums\FinancingReqStatusEnum;
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
        Schema::create('financing_verifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('financing_id');
            $table->enum('final_verification_status', array_column(FinancingReqStatusEnum::cases(), 'value'));
            $table->text('notes')->nullable();
            $table->uuid('verified_by')->nullable();
            $table->dateTime('verified_at')->nullable();

            $table->foreign('financing_id')->references('id')->on('financings')->onDelete('cascade');
            $table->foreign('verified_by')->references('id')->on('users')->onDelete('set null');
            $table->index('financing_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financing_verifications');
    }
};
