<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_journal_installment()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_journal_id  UUID;
                v_kas_ref     VARCHAR;
                v_piutang_ref VARCHAR;
                v_margin_ref  VARCHAR;
                v_principal   NUMERIC(15,2);
                v_margin      NUMERIC(15,2);
            BEGIN
                v_principal := COALESCE(NEW.principal_amount, 0);
                v_margin    := COALESCE(NEW.margin_amount, 0);

                IF v_principal = 0 AND v_margin = 0 THEN
                    RETURN NEW;
                END IF;

                IF round(v_principal + v_margin, 2) != round(NEW.nominal, 2) THEN
                    RAISE EXCEPTION
                        'Nominal tidak balance: pokok(%) + margin(%) != nominal(%)',
                        v_principal, v_margin, NEW.nominal;
                END IF;

                SELECT no_ref_account INTO v_kas_ref
                FROM accounts WHERE account_name = 'Kas' LIMIT 1;

                SELECT no_ref_account INTO v_piutang_ref
                FROM accounts WHERE account_name = 'Piutang Murabahah' LIMIT 1;

                SELECT no_ref_account INTO v_margin_ref
                FROM accounts WHERE account_name = 'Pendapatan Margin Murabahah' LIMIT 1;

                IF v_kas_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Kas tidak ditemukan';
                END IF;
                IF v_piutang_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Piutang Murabahah tidak ditemukan';
                END IF;
                IF v_margin_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Pendapatan Margin Murabahah tidak ditemukan';
                END IF;

                -- Buat header jurnal
                INSERT INTO journals (id, tgl_transaksi, created_by, created_at, updated_at)
                VALUES (
                    gen_random_uuid(),
                    NEW.payment_date::DATE,
                    NEW.updated_by,
                    NOW(), NOW()
                )
                RETURNING id INTO v_journal_id;

                -- Dr Kas
                INSERT INTO journal_entries (
                    journal_id, journal_group_id,
                    no_ref_account, position, nominal,
                    transaction_date, updated_by,
                    created_at, updated_at
                ) VALUES (
                    v_journal_id, v_journal_id,
                    v_kas_ref, 'Debit', NEW.nominal,
                    NEW.payment_date::DATE,
                    NEW.updated_by, NOW(), NOW()
                );

                -- Cr Piutang
                INSERT INTO journal_entries (
                    journal_id, journal_group_id,
                    no_ref_account, position, nominal,
                    transaction_date, updated_by,
                    created_at, updated_at
                ) VALUES (
                    v_journal_id, v_journal_id,
                    v_piutang_ref, 'Credit', v_principal,
                    NEW.payment_date::DATE,
                    NEW.updated_by, NOW(), NOW()
                );

                -- Cr Margin
                INSERT INTO journal_entries (
                    journal_id, journal_group_id,
                    no_ref_account, position, nominal,
                    transaction_date, updated_by,
                    created_at, updated_at
                ) VALUES (
                    v_journal_id, v_journal_id,
                    v_margin_ref, 'Credit', v_margin,
                    NEW.payment_date::DATE,
                    NEW.updated_by, NOW(), NOW()
                );

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void {}
};