<?php

use App\Enums\LoanStatusEnum;
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
        Schema::create('gallon_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('loan_date');
            $table->date('return_date')->nullable();
            $table->enum('loan_status', array_column(LoanStatusEnum::cases(), 'value'));

            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('amdk_product_id')->nullable()->constrained('amdk_products')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gallon_loans');
    }
};
