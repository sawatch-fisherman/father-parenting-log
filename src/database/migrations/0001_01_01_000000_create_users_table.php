<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * マイグレーションを実行する。
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('provider', 20);
            // 退会時に NULL を入れるため NULL 許容。MySQL は UNIQUE 内の NULL 同士を別物として
            // 扱うので、退会者が複数いても下記の UNIQUE(provider, provider_id) に衝突しない。
            $table->string('provider_id')->nullable();
            $table->rememberToken();
            // 退会日時（NULL = 在籍中）。退会は行を削除せず provider_id 等を破壊する in-place
            // 匿名化で行うため（行は care_logs の FK 親として残す必要がある）、SoftDeletes の
            // deleted_at は使わない（docs/decisions.md §1.1・docs/data-model.md ①）。
            $table->dateTime('withdrawn_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUlid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * マイグレーションをロールバックする。
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('sessions');
    }
};
