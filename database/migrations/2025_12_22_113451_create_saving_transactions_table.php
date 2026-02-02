<?php

use App\Enums\TransactionType;
use App\Enums\TransactionStatus;
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
        Schema::create('saving_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_code')->unique();
            $table->decimal('amount', 15, 2);
            $table->enum('type', array_column(TransactionType::cases(), 'value'));
            $table->enum('status', array_column(TransactionStatus::cases(), 'value'));
            $table->enum('method', array_column(TransactionMethods::cases(), 'value'));
            $table->text('description')->nullable();
            $table->dateTime('transaction_date');
            $table->foreignUuid('updated_by')->constrained('users');
            $table->foreignUuid('saving_account_id')->nullable()->constrained('saving_accounts')->nullOnDelete();
            $table->string('account_number')->nullable();
            $table->foreign('account_number')->references('account_number')->on('accounts')->nullOnDelete();
            $table->timestamps();

            $table->index('transaction_code');
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
