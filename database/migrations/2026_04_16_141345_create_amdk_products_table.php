<?php

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
        Schema::create('amdk_products', function (Blueprint $table) {
            $table->id();
            $table->string('amdk_product_code', 10)->unique();
            $table->string('amdk_product_name', 150);
            $table->integer('stock');
            $table->string('unit_measure', 50);
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->decimal('stokist_price', 15, 2)->nullable();
            $table->decimal('member_price', 15, 2)->nullable();
            $table->string('amdk_brand', 150)->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amdk_products');
    }
};
