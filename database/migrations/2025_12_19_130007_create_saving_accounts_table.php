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
            $table->enum('saving_type', array_column(SavingTypeEnum::cases(), 'value'));
            $table->integer('saving_tenor')->nullable();
            $table->decimal('target_amount', 15, 2)->nullable();
            
            $table->foreignUuid('user_id')->constrained('users')->onDelete('set null');
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
