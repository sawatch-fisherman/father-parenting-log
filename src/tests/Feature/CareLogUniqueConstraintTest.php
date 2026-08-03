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

    /**
     * クライアント側の送信ボタンdisableが間に合わなかった連打を、このUNIQUE制約が
     * セーフティネットとして防ぐ（docs/decisions.md §1.3）。`occurred_at` は秒精度で
     * 保存されるため、サブ秒しか違わない連打も同一秒に丸められてここで弾かれる
     * （切り捨て自体の検証は CareLogOccurredAtPrecisionTest）。
     */
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
}
