<?php

use App\Enums\FinancialCategoryEnum;
use App\Enums\FinancialTypeEnum;
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
            $table->enum('financial_type', array_column(FinancialTypeEnum::cases(), 'value'));
            $table->enum('category', array_column(FinancialCategoryEnum::cases(), 'value'));
            $table->decimal('amount', 15, 2);

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->unique(['member_id', 'financial_type']);
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
