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
        Schema::create('angsuran', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('tgl_jatuh_tempo');
            $table->integer('angsuran_ke');
            $table->decimal('nominal_angsuran', 15, 2);
            $table->enum('status', array_column(InstallmentPaymentScheduleStatusEnum::cases(), 'value'))->default(InstallmentPaymentScheduleStatusEnum::PENDING->value);
            $table->foreignUuid('pembiayaan_id')->nullable()->constrained('pembiayaan')->onDelete('set null');
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
