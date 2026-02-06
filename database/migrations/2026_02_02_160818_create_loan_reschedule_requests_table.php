<?php

use App\Enums\TransactionStatus;
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
        Schema::create('loan_reschedule_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_payment_schedule_id')->constrained('loan_payment_schedules')->onDelete('cascade');
            $table->date('requested_date')->nullable();
            $table->string('reason')->nullable();
            $table->enum('status', array_column(TransactionStatus::cases(), 'value'))->default('pending');
            $table->text('validation_notes')->nullable();
            $table->foreignUuid('checked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_reschedule_requests');
    }
};
