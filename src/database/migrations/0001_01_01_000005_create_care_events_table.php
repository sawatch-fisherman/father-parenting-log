<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('care_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('care_event_type_id')->constrained()->cascadeOnDelete();
            $table->dateTime('occurred_at', precision: 3);
            $table->string('memo', 255)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'care_event_type_id']);
            $table->index(['user_id', 'occurred_at']);
            $table->unique(['user_id', 'care_event_type_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('care_events');
    }
};
