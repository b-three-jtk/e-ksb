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
            CREATE OR REPLACE FUNCTION fn_journal_installment()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_group_id      UUID;
                v_kas_ref       VARCHAR;
                v_piutang_ref   VARCHAR;
                v_margin_ref    VARCHAR;
                v_principal     NUMERIC(15,2);
                v_margin        NUMERIC(15,2);
            BEGIN
                -- Ambil no_ref_account yang dibutuhkan
                SELECT no_ref_account INTO v_kas_ref
                FROM accounts WHERE account_name = 'Kas' LIMIT 1;

                SELECT no_ref_account INTO v_piutang_ref
                FROM accounts WHERE account_name = 'Piutang Murabahah' LIMIT 1;

                SELECT no_ref_account INTO v_margin_ref
                FROM accounts WHERE account_name = 'Pendapatan Margin Murabahah' LIMIT 1;

                -- Guard: pastikan semua akun ada
                IF v_kas_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Kas tidak ditemukan';
                END IF;
                IF v_piutang_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Piutang Murabahah tidak ditemukan';
                END IF;
                IF v_margin_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Pendapatan Margin Murabahah tidak ditemukan';
                END IF;

                -- Ambil breakdown dari kolom yang sudah diisi controller/service
                v_principal := COALESCE(NEW.principal_amount, 0);
                v_margin    := COALESCE(NEW.margin_amount, 0);

                -- Guard: nominal harus = pokok + margin
                IF round(v_principal + v_margin, 2) != round(NEW.nominal, 2) THEN
                    RAISE EXCEPTION 
                        'Nominal tidak balance: pokok(%) + margin(%) != nominal(%)',
                        v_principal, v_margin, NEW.nominal;
                END IF;

                v_group_id := gen_random_uuid();

                -- Dr Kas (total nominal)
                INSERT INTO journal_entries (
                    journal_group_id, no_ref_account, position,
                    nominal, transaction_date, updated_by,
                    created_at, updated_at
                ) VALUES (
                    v_group_id, v_kas_ref, 'Debit',
                    NEW.nominal,
                    NEW.payment_date::DATE,
                    NEW.updated_by,
                    NOW(), NOW()
                );

                -- Cr Piutang Murabahah (pokok)
                INSERT INTO journal_entries (
                    journal_group_id, no_ref_account, position,
                    nominal, transaction_date, updated_by,
                    created_at, updated_at
                ) VALUES (
                    v_group_id, v_piutang_ref, 'Credit',
                    v_principal,
                    NEW.payment_date::DATE,
                    NEW.updated_by,
                    NOW(), NOW()
                );

                -- Cr Pendapatan Margin Murabahah (margin)
                INSERT INTO journal_entries (
                    journal_group_id, no_ref_account, position,
                    nominal, transaction_date, updated_by,
                    created_at, updated_at
                ) VALUES (
                    v_group_id, v_margin_ref, 'Credit',
                    v_margin,
                    NEW.payment_date::DATE,
                    NEW.updated_by,
                    NOW(), NOW()
                );

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        DB::unprepared("
            CREATE TRIGGER trg_installment_journal
            AFTER INSERT ON installment_payment_transactions
            FOR EACH ROW
            EXECUTE FUNCTION fn_journal_installment();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_installment_journal ON installment_payment_transactions');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_journal_installment');
    }
};
