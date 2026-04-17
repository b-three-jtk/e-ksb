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
        Schema::create('collaterals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('financing_id')->constrained()->onDelete('cascade');
            $table->string('collateral_type');
            $table->string('collateral_proof')->nullable();
            $table->string('owner_name');
            $table->string('collateral_location')->nullable();
            $table->decimal('estimated_market_value', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaterals');
    }
};
