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
        Schema::create('amdk_stock_incoming_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('amdk_stock_incoming_id')->constrained('amdk_stock_incomings')->onDelete('cascade');
            $table->foreignId('amdk_product_id')->constrained('amdk_products')->onDelete('cascade');
            $table->integer('quantity');
            $table->string('unit_measure');
            $table->timestamps();

            $table->unique(['amdk_stock_incoming_id', 'amdk_product_id']);
            $table->index('amdk_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amdk_stock_incoming_items');
    }
};
