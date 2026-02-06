<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->decimal('total_loan', 15, 2);
            $table->integer('tenor');
            $table->decimal('monthly_installment', 15, 2);
            $table->decimal('remaining_principal', 15, 2);
            $table->decimal('remaining_margin', 15, 2);
            $table->foreignUuid('financing_id')->nullable()->constrained('financings')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
