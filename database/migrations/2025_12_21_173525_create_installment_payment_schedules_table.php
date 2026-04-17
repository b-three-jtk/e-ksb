<?php

use App\Enums\InstallmentPaymentScheduleStatusEnum;
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
        Schema::create('installment_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('due_date');
            $table->integer('installment_number');
            $table->enum('installment_schedule_status', array_column(InstallmentPaymentScheduleStatusEnum::cases(), 'value'))->default(InstallmentPaymentScheduleStatusEnum::SCHEDULED->value);
            $table->foreignId('installment_id')->constrained('installments')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_payment_schedules');
    }
};
