<?php

use App\Enums\UserStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pengguna', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kode_pengguna', 15)->unique();
            $table->string('foto_profil')->nullable();
            $table->string('nik', 16)->unique();
            $table->string('nama');
            $table->enum('status', array_column(UserStatusEnum::cases(), 'value'))->default('Aktif');
            $table->string('email')->unique()->nullable();
            $table->string('no_telp', 20)->unique();
            $table->date('tgl_bergabung')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();

            $table->index('nama');
            $table->index('kode_pengguna');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('no_telp')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengguna');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
