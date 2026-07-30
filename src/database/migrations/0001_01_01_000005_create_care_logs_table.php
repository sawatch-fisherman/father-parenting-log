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
        Schema::create('care_logs', function (Blueprint $table) {
            // ULID。`/care-logs/{care_log}` としてURLに露出する唯一のユーザー固有IDのため、
            // 連番だとID値そのものがサービス全体の総記録数と増加ペースを漏らす
            // （docs/decisions.md §1.3「主キー形式の判断基準」）。
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('care_action_id')->constrained()->cascadeOnDelete();
            $table->dateTime('occurred_at', precision: 3);
            $table->string('memo', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'occurred_at']);
            // 育児行動別集計用の (user_id, care_action_id) は、下記UNIQUEの左端プレフィックスで
            // 完全にカバーできるため単独のINDEXは張らない（最も行数が増えるログテーブルで、
            // INSERTごとの不要なインデックス更新と容量を避ける）。
            $table->unique(['user_id', 'care_action_id', 'occurred_at']);
        });
    }

    /**
     * マイグレーションをロールバックする。
     */
    public function down(): void
    {
        Schema::dropIfExists('care_logs');
    }
};
