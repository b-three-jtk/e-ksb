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
        Schema::create('saving_transactions', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['Penarikan', 'Penyetoran']);
            $table->enum('status', ['Belum Ditinjau', 'Ditolak dengan alasan', 'Selesai']);
            $table->enum('method', ['Tunai', 'Non-Tunai']);
            $table->text('description')->nullable();
            $table->dateTime('transaction_date');
            $table->foreignUuid('updated_by')->constrained('users');
            $table->string('saving_account_id');
            $table->foreign('saving_account_id')->references('id')->on('saving_accounts')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_transactions');
    }
};
