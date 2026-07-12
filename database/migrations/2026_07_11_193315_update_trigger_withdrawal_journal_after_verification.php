<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_saving_journal_withdrawal ON transaksi_simpanan');

        DB::unprepared("
            CREATE TRIGGER trg_saving_journal_withdrawal_verified
            AFTER UPDATE OF status ON transaksi_simpanan
            FOR EACH ROW
            WHEN (
                NEW.tipe_transaksi = 'Penarikan'
                AND NEW.status = 'Diverifikasi'
                AND OLD.status IS DISTINCT FROM 'Diverifikasi'
            )
            EXECUTE FUNCTION fn_journal_saving();
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_saving_journal_withdrawal_verified ON transaksi_simpanan');

        DB::unprepared("
            CREATE TRIGGER trg_saving_journal_withdrawal
            AFTER INSERT ON transaksi_simpanan
            FOR EACH ROW
            WHEN (NEW.tipe_transaksi = 'Penarikan')
            EXECUTE FUNCTION fn_journal_saving();
        ");
    }
};