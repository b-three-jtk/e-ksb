<?php

use App\Enums\Condition;
use App\Enums\FinancingReqStatus;
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
        Schema::create('financings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_code')->unique();
            $table->string('product_name');
            $table->string('product_type');
            $table->string('brand')->nullable();
            $table->string('color')->nullable();
            $table->enum('condition', array_column(Condition::cases(), 'value'))->nullable();
            $table->text('description');
            $table->decimal('cost_price', 15, 2)->nullable();
            $table->decimal('margin', 15, 2)->nullable();
            $table->decimal('tsaman_naqdy', 15, 2)->nullable();
            $table->integer('qty');
            $table->boolean('isWakalah')->nullable();
            $table->decimal('down_payment', 15, 2)->nullable();
            $table->date('akad_date')->nullable();
            $table->enum('status', array_column(FinancingReqStatus::cases(), 'value'));
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('updated_by')->constrained('users');
            $table->timestamps();

            $table->index('transaction_code');
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
