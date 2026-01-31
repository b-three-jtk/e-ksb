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
        Schema::create('akad_docs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('attachment');
            $table->timestamp('signed_at')->nullable();
            $table->foreignUuid('financing_id')->constrained('financings');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akad_docs');
    }
};
