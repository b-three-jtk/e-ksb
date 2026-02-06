<?php

use App\Enums\LoanPaymentScheduleStatus;
use App\Models\LoanPaymentSchedule;
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
        Schema::create('loan_payment_schedules', function (Blueprint $table) {
            $table->id();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('margin_amount', 15, 2);
            $table->date('due_date');
            $table->integer('installment_number');
            $table->enum('status', array_column(LoanPaymentScheduleStatus::cases(), 'value'))->default(LoanPaymentScheduleStatus::SCHEDULED->value);
            $table->foreignUuid('loan_id')->nullable()->constrained('loans')->onDelete('set null');
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
