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
            $table->string('name');
            $table->text('specification')->nullable();
            $table->integer('qty');
            $table->enum('condition', array_column(ConditionEnum::cases(), 'value'));
            $table->decimal('price_per_unit', 15, 2)->nullable();
            $table->string('purchase_receipt')->nullable();

            $table->foreignId('product_type_id')->nullable()->references('id')->on('product_types')->onDelete('set null');
            $table->foreignId('supplier_id')->nullable()->references('id')->on('suppliers')->onDelete('set null');
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
