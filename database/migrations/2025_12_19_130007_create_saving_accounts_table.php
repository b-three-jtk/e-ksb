<?php

use App\Enums\SavingTypeEnum;
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
        Schema::create('saving_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('saving_account_code', 20)->unique();
            $table->decimal('balance', 15, 2)->default(0);
            $table->enum('saving_type', array_column(SavingTypeEnum::cases(), 'value'));
            $table->unsignedBigInteger('member_id')->nullable();

            $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saving_accounts');
    }
};
