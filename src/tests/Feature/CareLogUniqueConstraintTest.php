<?php

namespace Tests\Feature;

use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CareLogUniqueConstraintTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_duplicate_user_action_and_occurred_at_is_rejected(): void
    {
        $user = User::factory()->create();
        $careAction = CareAction::factory()->create();
        $occurredAt = now();

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => $occurredAt,
        ]);

        $this->expectException(QueryException::class);

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => $occurredAt,
        ]);
    }

    /**
     * `occurred_at` は秒精度で保存されるため、サブ秒しか違わない2件は
     * 切り捨て後に同一秒となりUNIQUE制約で弾かれる。クライアント側の
     * 送信ボタンdisableが間に合わなかった連打を、このUNIQUE制約が
     * セーフティネットとして防ぐ（docs/decisions.md §1.3）。
     */
    public function test_two_logs_within_the_same_second_are_rejected(): void
    {
        $user = User::factory()->create();
        $careAction = CareAction::factory()->create();

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2026-07-29 12:34:56.100',
        ]);

        $this->expectException(QueryException::class);

        CareLog::factory()->create([
            'user_id' => $user->id,
            'care_action_id' => $careAction->id,
            'occurred_at' => '2026-07-29 12:34:56.900',
        ]);
    }
}
