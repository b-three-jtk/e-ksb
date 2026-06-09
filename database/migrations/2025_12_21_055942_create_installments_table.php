<?php

use App\Enums\InstallmentPaymentScheduleStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('due_date');
            $table->integer('installment_no');
            $table->decimal('amount', 15, 2);
            $table->enum('status', array_column(InstallmentPaymentScheduleStatusEnum::cases(), 'value'))->default(InstallmentPaymentScheduleStatusEnum::PENDING->value);
            $table->foreignUuid('financing_id')->nullable()->constrained('financings')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
