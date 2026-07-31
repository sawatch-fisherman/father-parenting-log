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
        Schema::create('titles', function (Blueprint $table) {
            // 連番。全ユーザー共通のSeeder固定マスタで、内容自体が公開情報のため
            // IDが規則的でも隠せる情報がない（docs/decisions.md §1.3「主キー形式の判断基準」）。
            $table->id();
            $table->foreignId('care_action_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name', 50);
            // 称号の段階（銅／銀／金）。`Lv.N`表記は使わず段階の表現をこの列に一本化する
            // （docs/decisions.md §1.3「称号の提示順・等級・一覧表示」）。
            $table->unsignedTinyInteger('grade');
            $table->unsignedTinyInteger('condition_type');
            $table->unsignedInteger('condition_value');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * マイグレーションをロールバックする。
     */
    public function down(): void
    {
        Schema::dropIfExists('titles');
    }
};
