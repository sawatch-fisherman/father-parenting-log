<?php

namespace Tests\Feature;

use App\Models\CareAction;
use App\Models\CareLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `care_logs.occurred_at` がミリ秒精度で保存・往復することを検証する。
 *
 * 素の `datetime` キャストだとグラマ既定の `Y-m-d H:i:s` で文字列化され、`DATETIME(3)` でも
 * ミリ秒が切り捨てられる。切り捨てられると二重送信防止のUNIQUEの判定粒度が秒になり、
 * 同一秒内の正当な2件目まで弾いてしまう。
 *
 * @see docs/data-model.md ④ `care_logs`
 */
class CareLogOccurredAtPrecisionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_occurred_at_is_stored_and_retrieved_with_millisecond_precision(): void
    {
        $careLog = CareLog::factory()->create([
            'occurred_at' => '2026-07-29 12:34:56.789',
        ]);

        $this->assertSame(
            '2026-07-29 12:34:56.789',
            DB::table('care_logs')->where('id', $careLog->id)->value('occurred_at'),
        );

        $this->assertSame('789', $careLog->fresh()?->occurred_at?->format('v'));
    }

    public function test_two_logs_one_millisecond_apart_are_both_accepted(): void
    {
        $user = User::factory()->create();
        $careAction = CareAction::factory()->create();

        foreach (['2026-07-29 12:34:56.789', '2026-07-29 12:34:56.790'] as $occurredAt) {
            CareLog::factory()->create([
                'user_id' => $user->id,
                'care_action_id' => $careAction->id,
                'occurred_at' => $occurredAt,
            ]);
        }

        $this->assertSame(2, CareLog::query()->count());
    }
}
