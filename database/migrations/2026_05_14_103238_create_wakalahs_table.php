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
        Schema::create('wakalahs', function (Blueprint $table) {
            $table->id();
            $table->decimal('nominal_wakalah', 15, 2);
            $table->date('akad_date');
            $table->string('signed_akad_document')->nullable();
            $table->uuid('financing_id');
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('financing_id')->references('id')->on('financings')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wakalahs');
    }
};
