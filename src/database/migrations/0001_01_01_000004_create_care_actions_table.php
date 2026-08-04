<?php

use App\Support\CareActionId;
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
        Schema::create('care_actions', function (Blueprint $table) {
            // 連番。露出するのは Phase 2 のカスタム育児行動 DELETE ルートのみで、そこから漏れるのは
            // 「全ユーザーのカスタム行の累計作成数」程度（docs/decisions.md §1.3「主キー形式の判断基準」）。
            $table->id();
            $table->foreignUlid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $this->reserveStandardIdRange();
    }

    /**
     * マイグレーションをロールバックする。
     */
    public function down(): void
    {
        Schema::dropIfExists('care_actions');
    }

    /**
     * TotoOps標準行（`user_id IS NULL`）用に `1` 〜 `CUSTOM_ID_FLOOR - 1` を予約する。
     *
     * 標準行とユーザーカスタム行を同一テーブルで管理するため、採番域を分けないと
     * 運用開始後に「18個目の標準行を追加したい」となった時点で標準側の連番が枯渇する。
     * カウンタを引き上げておけば以降のカスタム行は必ず予約域より上に採番されるので、
     * この分離はDB側が保証する（アプリ層のガードは不要）。
     *
     * @see docs/decisions.md §1.3「ID／主キーの形式」例外規定
     */
    private function reserveStandardIdRange(): void
    {
        $connection = Schema::getConnection();

        match ($connection->getDriverName()) {
            // MySQLは明示IDで低い値をINSERTしてもカウンタを下げないため、
            // Seederが1〜17を投入したあともこの値が保たれる。
            'mysql', 'mariadb' => $connection->statement(
                'ALTER TABLE care_actions AUTO_INCREMENT = '.CareActionId::CUSTOM_ID_FLOOR
            ),
            // SQLite（テスト用）は`sqlite_sequence`に「最後に採番した値」を持つ。
            'sqlite' => $connection->table('sqlite_sequence')->updateOrInsert(
                ['name' => 'care_actions'],
                ['seq' => CareActionId::CUSTOM_ID_FLOOR - 1]
            ),
            default => null,
        };
    }
};
