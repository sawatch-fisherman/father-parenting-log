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
            $table->unsignedTinyInteger('grade');
            $table->unsignedTinyInteger('condition_type');
            $table->unsignedInteger('condition_value');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            // 同一系統（育児行動 × 条件種別）内でしきい値が重複すると、図鑑の「未獲得の最小しきい値1件」が
            // 一意に定まらない（docs/decisions.md §1.3「称号の提示順・等級・一覧表示」）。
            // 全体合計称号（care_action_id IS NULL）はMySQLがUNIQUE内のNULL同士を別物として扱うため
            // この制約では守れず、そちらの重複は TitleUniqueConstraintTest で担保する。
            $table->unique(['care_action_id', 'condition_type', 'condition_value']);
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
