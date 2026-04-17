<?php

use App\Enums\EducationEnum;
use App\Enums\Gender;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('member_code', 10)->unique();
            $table->string('profile_picture')->nullable();
            $table->string('nik', 16)->unique();
            $table->string('name');
            $table->enum('gender', array_column(GenderEnum::cases(), 'value'))->nullable();
            $table->string('birth_place', 150)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('status', array_column(UserStatusEnum::cases(), 'value'))->default('Aktif');
            $table->text('domicile_address')->nullable();
            $table->text('residential_address')->nullable();
            $table->enum('marital_status', array_column(MaritalStatusEnum::cases(), 'value'))->nullable();
            $table->string('spouse_name')->nullable();
            $table->enum('last_education', array_column(EducationEnum::cases(), 'value'))->nullable();
            $table->integer('dependents')->nullable();
            $table->string('email')->unique();
            $table->string('phone_number', 20)->unique();
            $table->date('joined_date')->nullable();

            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('role_id')->constrained('roles');
            $table->rememberToken();
            $table->timestamps();

            $table->index('member_code');
            $table->index('name');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
