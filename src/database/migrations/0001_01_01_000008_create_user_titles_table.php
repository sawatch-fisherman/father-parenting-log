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
        Schema::create('user_titles', function (Blueprint $table) {
            // 連番。獲得済み称号に編集・削除の導線がなくIDがURL・APIに露出しない
            // （docs/decisions.md §1.3「主キー形式の判断基準」）。
            $table->id();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('title_id')->constrained()->restrictOnDelete();
            $table->timestamp('unlocked_at');
            $table->timestamps();

            $table->unique(['user_id', 'title_id']);
            $table->index(['user_id', 'unlocked_at']);
        });
    }

    /**
     * マイグレーションをロールバックする。
     */
    public function down(): void
    {
        Schema::dropIfExists('user_titles');
    }
};
