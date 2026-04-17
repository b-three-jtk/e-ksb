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
            $table->decimal('principal_paid', 15, 2);
            $table->decimal('margin_paid', 15, 2);
            $table->enum('installment_payment_method', array_column(PaymentMethodsEnum::cases(), 'value'));
            $table->boolean('is_early_repayment')->default(false);
            $table->datetime('payment_date');

            $table->foreignId('schedule_id')->nullable()->constrained('installment_payment_schedules')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
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
