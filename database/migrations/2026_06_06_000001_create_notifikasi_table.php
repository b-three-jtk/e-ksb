<?php

use App\Enums\NotificationReminderTypeEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anggota_id')->constrained('anggota')->cascadeOnDelete();
            $table->string('judul', 255);
            $table->text('pesan');
            $table->enum('jenis_notifikasi', array_column(NotificationTypeEnum::cases(), 'value'));
            $table->string('jenis_referensi')->nullable();
            $table->string('referensi_id')->nullable();
            $table->string('periode_notifikasi', 7);
            $table->enum('jenis_pengingat', array_column(NotificationReminderTypeEnum::cases(), 'value'));
            $table->enum('status', ['draf', 'terkirim', 'gagal_kirim'])->default('draf');
            $table->boolean('sudah_dibaca')->default(false);
            $table->timestamp('dijadwalkan_pada')->nullable();
            $table->timestamp('dikirim_pada')->nullable();
            $table->timestamp('dibaca_pada')->nullable();
            $table->timestamp('peringatan_ditampilkan_pada')->nullable();
            $table->timestamps();

            $table->index(['anggota_id', 'jenis_notifikasi', 'periode_notifikasi', 'jenis_pengingat']);
            $table->index(['jenis_referensi', 'referensi_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
