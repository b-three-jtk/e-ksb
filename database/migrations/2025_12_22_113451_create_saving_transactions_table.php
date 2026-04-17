<?php

use App\Enums\PaymentMethodsEnum;
use App\Enums\TransactionTypeEnum;
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
        Schema::create('saving_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('saving_transaction_code', 10)->unique();
            $table->decimal('saving_amount', 15, 2);
            $table->enum('transaction_type', array_column(TransactionTypeEnum::cases(), 'value'));
            $table->enum('saving_payment_method', array_column(PaymentMethodsEnum::cases(), 'value'));
            $table->text('saving_description')->nullable();
            $table->datetime('transaction_date');

            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('saving_account_id')->nullable()->constrained('saving_accounts')->onDelete('set null');
            $table->string('account_number')->nullable();
            $table->foreign('account_number')->references('account_number')->on('accounts')->onDelete('set null');
            $table->foreignId('point_id')->nullable()->constrained('point_transactions')->onDelete('set null');
            $table->timestamps();

            $table->index('saving_transaction_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_transactions');
    }
};
