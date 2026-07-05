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
                v_jenis_simpanan VARCHAR;
            BEGIN
                -- Ambil jenis_simpanan dari akun_simpanan
                SELECT jenis_simpanan INTO v_jenis_simpanan
                FROM akun_simpanan
                WHERE id = NEW.akun_simpanan_id;

                -- Kas → no_ref_akun '101'
                SELECT no_ref_akun INTO v_kas_ref
                FROM akun
                WHERE nama_akun = 'Kas'
                LIMIT 1;

                -- Simpanan → cocokkan dengan jenis_simpanan
                -- Nilai yang valid: 'Tabungan Anggota', 'Tabungan Berjangka',
                --                   'Tabungan Ibadah', 'Simpanan Pokok', 'Simpanan Wajib'
                SELECT no_ref_akun INTO v_saving_ref
                FROM akun
                WHERE nama_akun = v_jenis_simpanan
                LIMIT 1;

                -- Guard: kalau akun tidak ditemukan, batalkan dan kasih pesan jelas
                IF v_kas_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Kas tidak ditemukan di tabel akun';
                END IF;

                IF v_saving_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun untuk jenis_simpanan ''%'' tidak ditemukan di tabel akun',
                        v_jenis_simpanan;
                END IF;

                -- Arah jurnal
                IF NEW.tipe_transaksi = 'Penyetoran' THEN
                    v_debit_ref  := v_kas_ref;
                    v_credit_ref := v_saving_ref;

                ELSIF NEW.tipe_transaksi = 'Penarikan' THEN
                    v_debit_ref  := v_saving_ref;
                    v_credit_ref := v_kas_ref;

                ELSE
                    RETURN NEW;
                END IF;

                v_group_id := gen_random_uuid();

                -- Baris DEBIT
                INSERT INTO detail_jurnal (
                    jurnal_id,
                    no_ref_akun,
                    posisi_akun,
                    nominal,
                    updated_by,
                    created_at,
                    updated_at
                ) VALUES (
                    v_group_id,
                    v_debit_ref,
                    'Debit',
                    NEW.nominal_simpanan,
                    NEW.updated_by,
                    NOW(),
                    NOW()
                );

                -- Baris CREDIT
                INSERT INTO detail_jurnal (
                    jurnal_id,
                    no_ref_akun,
                    posisi_akun,
                    nominal,
                    updated_by,
                    created_at,
                    updated_at
                ) VALUES (
                    v_group_id,
                    v_credit_ref,
                    'Credit',
                    NEW.nominal_simpanan,
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
            AFTER INSERT ON transaksi_simpanan
            FOR EACH ROW
            EXECUTE FUNCTION fn_journal_saving();
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_saving_journal ON transaksi_simpanan');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_journal_saving');
    }
};