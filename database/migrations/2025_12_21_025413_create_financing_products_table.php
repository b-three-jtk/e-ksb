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
        Schema::create('financing_products', function (Blueprint $table) {
            $table->id();
            $table->text('request_description');
            $table->integer('qty');
            $table->enum('condition', array_column(ConditionEnum::cases(), 'value'));
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->decimal('margin_amount', 10, 2)->nullable();
            $table->string('purchase_receipt')->nullable();

            $table->foreignId('product_id')->references('id')->on('products')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('f_inancing_products');
    }
};
