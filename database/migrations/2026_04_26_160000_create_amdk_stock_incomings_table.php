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
        Schema::create('amdk_stock_incomings', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->text('notes')->nullable();
            $table->date('incoming_date');
            $table->string('receive_receipt')->nullable();
            $table->uuid('updated_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->foreign('updated_by')->references('id')->on('users');
            $table->index('incoming_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amdk_stock_incomings');
    }
};
