<?php

use App\Enums\LoanPaymentStatus;
use App\Enums\TransactionMethods;
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
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_code')->unique();
            $table->decimal('amount', 15, 2);
            $table->decimal('principal_paid', 15, 2);
            $table->decimal('margin_paid', 15, 2);
            $table->enum('status', array_column(LoanPaymentStatus::cases(), 'value'));
            $table->enum('method', array_column(TransactionMethods::cases(), 'value'));
            $table->string('attachment')->nullable();
            $table->boolean('is_early_repayment')->default(false);
            $table->foreignId('loan_payment_schedule_id')->nullable()->constrained('loan_payment_schedules')->onDelete('set null');
            $table->date('payment_date')->nullable();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
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
