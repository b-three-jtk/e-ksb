<?php

use App\Enums\BuyerTypeEnum;
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
        Schema::create('amdk_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('invoice_number')->unique();
            $table->enum('payment_method', array_column(PaymentMethodsEnum::cases(), 'value'));
            $table->enum('buyer_type', array_column(BuyerTypeEnum::cases(), 'value'));
            $table->string('invoice_receipt')->nullable();
            $table->unsignedBigInteger('member_id')->nullable();

            $table->foreignId('point_id')->nullable()->constrained('point_transactions')->onDelete('set null');
            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
            $table->foreignUuid('stokist_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_transactions');
    }
};
