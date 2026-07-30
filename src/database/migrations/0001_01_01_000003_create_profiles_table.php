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
        Schema::create('profiles', function (Blueprint $table) {
            // 連番。プロフィールは常に「自分自身」に暗黙スコープされIDがURL・APIに一切露出しないため、
            // ULID化しても隠せる情報がない（docs/decisions.md §1.3「主キー形式の判断基準」）。
            $table->id();
            $table->foreignUlid('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nickname', 50);
            $table->unsignedTinyInteger('age_group');
            $table->unsignedTinyInteger('child_age_group');
            $table->timestamps();
        });
    }

    /**
     * マイグレーションをロールバックする。
     */
    public function down(): void
    {
        Schema::dropIfExists('profiles');
    }
};
