<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus trigger lama (AFTER INSERT untuk semua tipe transaksi)
        DB::unprepared('DROP TRIGGER IF EXISTS trg_saving_journal ON transaksi_simpanan');

        // Definisikan ulang function-nya (identik dengan migration sebelumnya,
        // dibuat self-contained supaya migration ini aman dijalankan dari fresh migrate)
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

                INSERT INTO jurnal (id, tgl_transaksi, created_by, created_at, updated_at)
                VALUES (
                    gen_random_uuid(),
                    NEW.tgl_transaksi::DATE,
                    NEW.updated_by,
                    NOW(), NOW()
                )
                RETURNING id INTO v_journal_id;

                INSERT INTO detail_jurnal (
                    jurnal_id, no_ref_akun, posisi_akun, nominal,
                    updated_by, created_at, updated_at
                ) VALUES (
                    v_journal_id, v_debit_ref, 'Debit', NEW.nominal_simpanan,
                    NEW.updated_by, NOW(), NOW()
                );

                INSERT INTO detail_jurnal (
                    jurnal_id, no_ref_akun, posisi_akun, nominal,
                    updated_by, created_at, updated_at
                ) VALUES (
                    v_journal_id, v_credit_ref, 'Credit', NEW.nominal_simpanan,
                    NEW.updated_by, NOW(), NOW()
                );

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Trigger 1: Penarikan tetap dijurnal saat INSERT (tidak melalui alur verifikasi Bendahara)
        DB::unprepared("
            CREATE TRIGGER trg_saving_journal_withdrawal
            AFTER INSERT ON transaksi_simpanan
            FOR EACH ROW
            WHEN (NEW.tipe_transaksi = 'Penarikan')
            EXECUTE FUNCTION fn_journal_saving();
        ");

        // Trigger 2: Penyetoran dijurnal HANYA saat status berubah jadi 'Diverifikasi'
        // (yaitu saat Bendahara klik tombol "Diterima" -> verifyDeposit())
        DB::unprepared("
            CREATE TRIGGER trg_saving_journal_deposit_verified
            AFTER UPDATE OF status ON transaksi_simpanan
            FOR EACH ROW
            WHEN (
                NEW.tipe_transaksi = 'Penyetoran'
                AND NEW.status = 'Diverifikasi'
                AND OLD.status IS DISTINCT FROM 'Diverifikasi'
            )
            EXECUTE FUNCTION fn_journal_saving();
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_saving_journal_withdrawal ON transaksi_simpanan');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_saving_journal_deposit_verified ON transaksi_simpanan');

        // Kembalikan trigger lama: AFTER INSERT untuk semua tipe transaksi
        DB::unprepared("
            CREATE TRIGGER trg_saving_journal
            AFTER INSERT ON transaksi_simpanan
            FOR EACH ROW
            EXECUTE FUNCTION fn_journal_saving();
        ");
    }
};