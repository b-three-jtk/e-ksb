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
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('no_ref_account');
            $table->enum('position', ['Debit', 'Credit']);
            $table->decimal('nominal', 15, 2);
            $table->date('transaction_date');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('no_ref_account')->references('no_ref_account')->on('accounts')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->index('no_ref_account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
