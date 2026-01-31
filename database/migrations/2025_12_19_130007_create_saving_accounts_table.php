<?php

use App\Enums\SavingType;
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
            $table->string('account_number')->unique();
            $table->decimal('balance', 15, 2);
            $table->enum('type', array_column(SavingType::cases(), 'value'));
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
