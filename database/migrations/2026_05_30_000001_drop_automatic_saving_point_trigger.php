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
        DB::unprepared('DROP TRIGGER IF EXISTS calculate_point_for_saving ON transaksi_simpanan;');
        DB::unprepared('DROP FUNCTION IF EXISTS calculate_point_for_saving();');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared(<<<'SQL'
CREATE OR REPLACE FUNCTION calculate_point_for_saving()
RETURNS TRIGGER AS $$
DECLARE
    points_earned INTEGER;
    activity_desc TEXT;
    v_pengguna_id UUID;
    v_point_trans_id INTEGER;
BEGIN
IF NEW.saldo_setelah_transaksi < 100000 THEN
    RETURN NEW;
ELSE
    points_earned := FLOOR(NEW.saldo_setelah_transaksi / 100000);
    activity_desc := 'Mendapatkan ' || points_earned || ' poin dari transaksi sebesar ' || NEW.nominal_simpanan;

    SELECT pengguna_id INTO v_pengguna_id FROM anggota WHERE id = (SELECT anggota_id FROM akun_simpanan WHERE id = NEW.akun_simpanan_id);

    INSERT INTO poin (jml_perolehan, deskripsi, pengguna_id, created_at, updated_at)
    VALUES (points_earned, activity_desc, v_pengguna_id, NOW(), NOW())
    RETURNING id INTO v_point_trans_id;

    NEW.poin_id := v_point_trans_id;

    RETURN NEW;
END IF;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER calculate_point_for_saving
BEFORE INSERT ON transaksi_simpanan
FOR EACH ROW
EXECUTE FUNCTION calculate_point_for_saving();
SQL);
    }
};