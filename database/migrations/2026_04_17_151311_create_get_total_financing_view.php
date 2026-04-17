<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE VIEW get_total_financing AS
            SELECT
            created_at,
            SUM(margin_amount) + SUM(cost_price) AS total_financing
        FROM financing_products
        GROUP BY created_at");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS get_total_financing");
    }
};
