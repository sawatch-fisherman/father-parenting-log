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
            $table->dateTime('occurred_at');
            // 記録時点の年代・末子の年齢帯のスナップショット（profiles からコピーする）。
            // profiles 側の値は可変なので、集計時に JOIN すると「現在の年代」でバケットされ、
            // ユーザーが child_age_group を更新した瞬間に過去ログが遡って別の年代へ移動してしまう
            // （docs/decisions.md §1.3「集計軸に使う属性はログ側にスナップショットする」）。
            // Phase 2 の全体傾向集計は aggregate_* 経由になる想定で、MVP でこの2列を検索条件に
            // 使うクエリは無いため、インデックスは張らない。
            $table->unsignedTinyInteger('age_group');
            $table->unsignedTinyInteger('child_age_group');
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
