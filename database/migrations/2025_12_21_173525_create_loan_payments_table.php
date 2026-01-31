<?php

use App\Enums\LoanStatus;
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
            $table->enum('status', array_column(LoanStatus::cases(), 'value'));
            $table->enum('method', array_column(TransactionMethods::cases(), 'value'));
            $table->string('attachment');
            $table->foreignUuid('loan_id')->nullable()->constrained('loans')->onDelete('set null');
            $table->date('payment_date');
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
