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
        Schema::table('saving_transactions', function (Blueprint $table) {
            $table->string('saving_transaction_code', 20)->change();
        });

        Schema::table('saving_accounts', function (Blueprint $table) {
            $table->string('saving_account_code', 20)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saving_transactions', function (Blueprint $table) {
            $table->string('saving_transaction_code', 10)->change();
        });

        Schema::table('saving_accounts', function (Blueprint $table) {
            $table->string('saving_account_code', 10)->change();
        });
    }
};
