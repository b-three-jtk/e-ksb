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
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->date('calculation_period')->nullable()->after('activity_description');
            $table->decimal('saving_balance_snapshot', 15, 2)->default(0)->after('calculation_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('point_transactions', function (Blueprint $table) {
            $table->dropColumn(['calculation_period', 'saving_balance_snapshot']);
        });
    }
};