<?php

use App\Enums\FinancingReqStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $statuses = "'" . implode("', '", array_column(FinancingReqStatusEnum::cases(), 'value')) . "'";

        DB::statement("ALTER TABLE financings DROP CONSTRAINT IF EXISTS financings_status_check");

        DB::statement("ALTER TABLE financings ADD CONSTRAINT financings_status_check CHECK (status IN ($statuses))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $oldStatusesArray = [
            'Menunggu Kelengkapan Dokumen',
            'Belum Ditinjau',
            'Disetujui',
            'Disetujui dengan Catatan',
            'Ditolak',
            'Angsuran Berjalan',
            'Lunas',
        ];

        $oldStatuses = "'" . implode("', '", $oldStatusesArray) . "'";

        DB::statement("ALTER TABLE financings DROP CONSTRAINT IF EXISTS financings_status_check");
        DB::statement("ALTER TABLE financings ADD CONSTRAINT financings_status_check CHECK (status IN ($oldStatuses))");
    }
};
