<?php

use App\Enums\FinancingPaymentMethodEnum;
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
        Schema::create('financings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('financing_transaction_code')->unique();
            $table->decimal('down_payment', 15, 2)->nullable();
            $table->enum('status', array_column(FinancingReqStatusEnum::cases(), 'value'))->default(FinancingReqStatusEnum::WAITING_DOCUMENTS->value);
            $table->enum('payment_method', array_column(FinancingPaymentMethodEnum::cases(), 'value'))->nullable();
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('margin_amount', 15, 2)->nullable();
            $table->string('signed_akad_document')->nullable();
            $table->date('requested_date')->nullable();
            $table->date('akad_date')->nullable();
            $table->date('paid_date')->nullable();
            $table->integer('tenor')->nullable();

            // set null so that if the member is deleted, the financing record will not be deleted but the id fk will be set to null
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->foreignId('member_id')->references('id')->on('members')->onDelete('set null');
            $table->index('financing_transaction_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financings');
    }
};
