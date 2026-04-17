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
        Schema::create('stokist_products', function (Blueprint $table) {
            $table->foreignUuid('stokist_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('amdk_product_id')->constrained('amdk_products')->onDelete('cascade');
            $table->decimal('non_member_price', 10, 2);
            $table->timestamps();

            $table->primary(['stokist_id', 'amdk_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stokist_products');
    }
};
