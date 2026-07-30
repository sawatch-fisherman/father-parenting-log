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
        Schema::create('user_slot_configs', function (Blueprint $table) {
            // 連番。ピン留めは (user_id, slot_position) をキーにupsertするだけで、
            // IDがURL・APIに露出しない（docs/decisions.md §1.3「主キー形式の判断基準」）。
            $table->id();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_position');
            $table->foreignId('care_action_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'slot_position']);
            $table->unique(['user_id', 'care_action_id']);
        });
    }

    /**
     * マイグレーションをロールバックする。
     */
    public function down(): void
    {
        Schema::dropIfExists('user_slot_configs');
    }
};
