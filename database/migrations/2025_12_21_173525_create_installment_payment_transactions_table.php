<?php

use App\Enums\PaymentMethodsEnum;
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
        Schema::create('installment_payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('installment_trans_code', 10)->unique();
            $table->decimal('nominal', 15, 2);
            $table->enum('payment_method', array_column(PaymentMethodsEnum::cases(), 'value'));
            $table->boolean('is_early_repayment')->default(false);
            $table->datetime('payment_date');
            $table->string('installment_payment_receipt')->nullable();

            $table->foreignUuid('installment_id')->nullable()->references('id')->on('installments')->onDelete('set null');
            $table->foreignUuid('updated_by')->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
    }
};
