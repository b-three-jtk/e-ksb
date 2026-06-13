<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_journal_saving()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_group_id    UUID;
                v_kas_ref     VARCHAR;
                v_saving_ref  VARCHAR;
                v_debit_ref   VARCHAR;
                v_credit_ref  VARCHAR;
                v_saving_type VARCHAR;
            BEGIN
                -- Ambil saving_type dari saving_accounts
                SELECT saving_type INTO v_saving_type
                FROM saving_accounts
                WHERE id = NEW.saving_account_id;

                -- Kas → no_ref_account '101'
                SELECT no_ref_account INTO v_kas_ref
                FROM accounts
                WHERE account_name = 'Kas'
                LIMIT 1;

                -- Simpanan → cocokkan dengan saving_type
                -- Nilai yang valid: 'Tabungan Anggota', 'Tabungan Berjangka',
                --                   'Tabungan Ibadah', 'Simpanan Pokok', 'Simpanan Wajib'
                SELECT no_ref_account INTO v_saving_ref
                FROM accounts
                WHERE account_name = v_saving_type
                LIMIT 1;

                -- Guard: kalau akun tidak ditemukan, batalkan dan kasih pesan jelas
                IF v_kas_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Kas tidak ditemukan di tabel accounts';
                END IF;

                IF v_saving_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun untuk saving_type ''%'' tidak ditemukan di tabel accounts',
                        v_saving_type;
                END IF;

                -- Arah jurnal
                IF NEW.transaction_type = 'Penyetoran' THEN
                    v_debit_ref  := v_kas_ref;
                    v_credit_ref := v_saving_ref;

                ELSIF NEW.transaction_type = 'Penarikan' THEN
                    v_debit_ref  := v_saving_ref;
                    v_credit_ref := v_kas_ref;

                ELSE
                    RETURN NEW;
                END IF;

                v_group_id := gen_random_uuid();

                -- Baris DEBIT
                INSERT INTO journal_entries (
                    journal_group_id,
                    no_ref_account,
                    position,
                    nominal,
                    transaction_date,
                    updated_by,
                    created_at,
                    updated_at
                ) VALUES (
                    v_group_id,
                    v_debit_ref,
                    'Debit',
                    NEW.saving_amount,
                    NEW.transaction_date::DATE,
                    NEW.updated_by,
                    NOW(),
                    NOW()
                );

                -- Baris CREDIT
                INSERT INTO journal_entries (
                    journal_group_id,
                    no_ref_account,
                    position,
                    nominal,
                    transaction_date,
                    updated_by,
                    created_at,
                    updated_at
                ) VALUES (
                    v_group_id,
                    v_credit_ref,
                    'Credit',
                    NEW.saving_amount,
                    NEW.transaction_date::DATE,
                    NEW.updated_by,
                    NOW(),
                    NOW()
                );

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            CREATE TRIGGER trg_saving_journal
            AFTER INSERT ON saving_transactions
            FOR EACH ROW
            EXECUTE FUNCTION fn_journal_saving();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_saving_journal ON saving_transactions');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_journal_saving');
    }
};