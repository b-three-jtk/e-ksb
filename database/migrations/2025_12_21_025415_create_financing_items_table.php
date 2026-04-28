<?php

use App\Enums\ConditionEnum;
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
        Schema::create('financing_items', function (Blueprint $table) {
            $table->id();
            $table->text('request_description');
            $table->integer('qty');
            $table->enum('condition', array_column(ConditionEnum::cases(), 'value'));
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('margin_amount', 10, 2)->nullable();
            $table->string('purchase_receipt')->nullable();

            $table->foreignId('product_id')->references('id')->on('financing_products')->onDelete('set null');
            $table->foreignId('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreignUuid('financing_id')->references('id')->on('financings')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financing_items');
    }
};
