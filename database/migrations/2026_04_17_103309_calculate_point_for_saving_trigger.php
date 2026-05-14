<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // postgreSQL trigger to calculate points for saving transactions
        DB::unprepared('
            CREATE OR REPLACE FUNCTION calculate_point_for_saving()
            RETURNS TRIGGER AS $$
            DECLARE
                points_earned INTEGER;
                activity_desc TEXT;
            BEGIN
                IF NEW.transaction_type = \'Penarikan\' AND NEW.saving_amount > 100000 THEN
                    points_earned := FLOOR(NEW.balance_after_transaction / 100000);
                    activity_desc := \'Mendapatkan \' || points_earned || \' poin untuk penarikan sebesar \' || NEW.saving_amount;
                    INSERT INTO point_transactions (amount_earned, activity_description, user_id, created_at, updated_at)
                    VALUES (points_earned, activity_desc, (SELECT user_id FROM members WHERE id = (SELECT member_id FROM saving_accounts WHERE id = NEW.saving_account_id)), NOW(), NOW());
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');
        DB::unprepared('
            CREATE TRIGGER calculate_point_for_saving
            BEFORE INSERT ON saving_transactions
            FOR EACH ROW
            EXECUTE FUNCTION calculate_point_for_saving();
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS calculate_point_for_saving ON saving_transactions;');
    }
};
