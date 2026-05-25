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
            financings.member_id,
            financing_items.created_at,
            financings.status,
            SUM(margin_amount) + SUM(cost_price) AS total_financing
        FROM financing_items
        JOIN financings ON financing_items.financing_id = financings.id
        GROUP BY financings.member_id, financing_items.created_at, financings.status");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS get_total_financing");
    }
};
