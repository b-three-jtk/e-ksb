<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION fn_journal_saving()
            RETURNS TRIGGER AS \$\$
            DECLARE
                v_journal_id  UUID;
                v_kas_ref     VARCHAR;
                v_saving_ref  VARCHAR;
                v_debit_ref   VARCHAR;
                v_credit_ref  VARCHAR;
                v_jenis_simpanan VARCHAR;
            BEGIN
                SELECT jenis_simpanan INTO v_jenis_simpanan
                FROM akun_simpanan
                WHERE id = NEW.akun_simpanan_id;

                SELECT no_ref_akun INTO v_kas_ref
                FROM akun WHERE nama_akun = 'Kas' LIMIT 1;

                SELECT no_ref_akun INTO v_saving_ref
                FROM akun WHERE nama_akun = v_jenis_simpanan LIMIT 1;

                IF v_kas_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Kas tidak ditemukan';
                END IF;
                IF v_saving_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun untuk jenis_simpanan ''%'' tidak ditemukan', v_jenis_simpanan;
                END IF;

                IF NEW.tipe_transaksi = 'Penyetoran' THEN
                    v_debit_ref  := v_kas_ref;
                    v_credit_ref := v_saving_ref;
                ELSIF NEW.tipe_transaksi = 'Penarikan' THEN
                    v_debit_ref  := v_saving_ref;
                    v_credit_ref := v_kas_ref;
                ELSE
                    RETURN NEW;
                END IF;

                -- Buat header jurnal dulu
                INSERT INTO journals (id, tgl_transaksi, created_by, created_at, updated_at)
                VALUES (
                    gen_random_uuid(),
                    NEW.tgl_transaksi::DATE,
                    NEW.updated_by,
                    NOW(), NOW()
                )
                RETURNING id INTO v_journal_id;

                -- Debit
                INSERT INTO journal_entries (
                    journal_id, journal_group_id,
                    no_ref_akun, position, nominal,
                    tgl_transaksi, updated_by,
                    created_at, updated_at
                ) VALUES (
                    v_journal_id, v_journal_id,
                    v_debit_ref, 'Debit',
                    NEW.nominal_simpanan,
                    NEW.tgl_transaksi::DATE,
                    NEW.updated_by, NOW(), NOW()
                );

                -- Credit
                INSERT INTO journal_entries (
                    journal_id, journal_group_id,
                    no_ref_akun, position, nominal,
                    tgl_transaksi, updated_by,
                    created_at, updated_at
                ) VALUES (
                    v_journal_id, v_journal_id,
                    v_credit_ref, 'Credit',
                    NEW.nominal_simpanan,
                    NEW.tgl_transaksi::DATE,
                    NEW.updated_by, NOW(), NOW()
                );

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
    }

    public function down(): void
    {
        //
    }
};
