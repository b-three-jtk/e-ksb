<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE OR REPLACE VIEW get_saving_account_balance AS
            SELECT
                saving_accounts.member_id,
                saving_account_id,
                SUM(CASE WHEN transaction_type = 'Penyetoran' THEN saving_amount ELSE 0 END) -
                SUM(CASE WHEN transaction_type = 'Penarikan' THEN saving_amount ELSE 0 END) AS total_balance
            FROM saving_transactions
            JOIN saving_accounts ON saving_transactions.saving_account_id = saving_accounts.id
            GROUP BY saving_account_id, saving_accounts.member_id
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
