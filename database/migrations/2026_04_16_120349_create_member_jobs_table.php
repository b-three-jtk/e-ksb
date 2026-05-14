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
        Schema::create('member_jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('employment_status');
            $table->string('job_title');
            $table->string('company_or_business_name')->nullable();
            $table->string('business_field')->nullable();
            $table->integer('tenure_year')->nullable();
            $table->text('workplace_address')->nullable();
            $table->string('workplace_contact', 20)->nullable();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_jobs');
    }
};
