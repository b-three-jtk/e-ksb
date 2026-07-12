<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus trigger lama (AFTER INSERT)
        DB::unprepared('DROP TRIGGER IF EXISTS trg_installment_journal ON pembayaran_angsuran');

        // Function-nya tetap sama persis seperti update_trigger_installment_journal,
        // didefinisikan ulang di sini supaya migration ini self-contained
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
                v_principal := COALESCE(NEW.pokok_dibayar, 0);
                v_margin    := COALESCE(NEW.margin_dibayar, 0);

                IF v_principal = 0 AND v_margin = 0 THEN
                    RETURN NEW;
                END IF;

                IF round(v_principal + v_margin, 2) != round(NEW.jumlah_angsuran_dibayar, 2) THEN
                    RAISE EXCEPTION
                        'Nominal tidak balance: pokok(%) + margin(%) != nominal(%)',
                        v_principal, v_margin, NEW.jumlah_angsuran_dibayar;
                END IF;

                SELECT no_ref_akun INTO v_kas_ref
                FROM akun WHERE nama_akun = 'Kas' LIMIT 1;

                SELECT no_ref_akun INTO v_piutang_ref
                FROM akun WHERE nama_akun = 'Piutang Murabahah' LIMIT 1;

                SELECT no_ref_akun INTO v_margin_ref
                FROM akun WHERE nama_akun = 'Pendapatan Margin Murabahah' LIMIT 1;

                IF v_kas_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Kas tidak ditemukan';
                END IF;
                IF v_piutang_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Piutang Murabahah tidak ditemukan';
                END IF;
                IF v_margin_ref IS NULL THEN
                    RAISE EXCEPTION 'Akun Pendapatan Margin Murabahah tidak ditemukan';
                END IF;

                INSERT INTO jurnal (id, tgl_transaksi, created_by, created_at, updated_at)
                VALUES (
                    gen_random_uuid(),
                    NEW.tgl_pembayaran::DATE,
                    NEW.updated_by,
                    NOW(), NOW()
                )
                RETURNING id INTO v_journal_id;

                INSERT INTO detail_jurnal (
                    jurnal_id, no_ref_akun, posisi_akun, nominal,
                    updated_by, created_at, updated_at
                ) VALUES (
                    v_journal_id, v_kas_ref, 'Debit', NEW.jumlah_angsuran_dibayar,
                    NEW.updated_by, NOW(), NOW()
                );

                INSERT INTO detail_jurnal (
                    jurnal_id, no_ref_akun, posisi_akun, nominal,
                    updated_by, created_at, updated_at
                ) VALUES (
                    v_journal_id, v_piutang_ref, 'Credit', v_principal,
                    NEW.updated_by, NOW(), NOW()
                );

                INSERT INTO detail_jurnal (
                    jurnal_id, no_ref_akun, posisi_akun, nominal,
                    updated_by, created_at, updated_at
                ) VALUES (
                    v_journal_id, v_margin_ref, 'Credit', v_margin,
                    NEW.updated_by, NOW(), NOW()
                );

                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");

        // Jurnal baru dibuat HANYA saat status berubah jadi 'Diverifikasi'
        // (yaitu saat Bendahara memverifikasi pembayaran angsuran)
        DB::unprepared("
            CREATE TRIGGER trg_installment_journal_verified
            AFTER UPDATE OF status ON pembayaran_angsuran
            FOR EACH ROW
            WHEN (
                NEW.status = 'Diverifikasi'
                AND OLD.status IS DISTINCT FROM 'Diverifikasi'
            )
            EXECUTE FUNCTION fn_journal_installment();
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_installment_journal_verified ON pembayaran_angsuran');

        DB::unprepared("
            CREATE TRIGGER trg_installment_journal
            AFTER INSERT ON pembayaran_angsuran
            FOR EACH ROW
            EXECUTE FUNCTION fn_journal_installment();
        ");
    }
};