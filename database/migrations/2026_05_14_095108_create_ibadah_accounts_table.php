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
        Schema::create('ibadah_accounts', function (Blueprint $table) {
            $table->id();
            $table->integer('tenor');
            $table->decimal('target_amount', 15, 2);
            $table->uuid('saving_account_id');

            $table->foreign('saving_account_id')->references('id')->on('saving_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ibadah_accounts');
    }
};
