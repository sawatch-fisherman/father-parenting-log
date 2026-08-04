<?php

namespace Tests\Feature;

use App\Models\CareLog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * `care_logs.occurred_at` が秒精度で保存・往復し、サブ秒（ミリ秒等）は
 * 書き込み時に切り捨てられることを検証する。
 *
 * @see docs/data-model.md ④ `care_logs`
 */
class CareLogOccurredAtPrecisionTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * ミリ秒付きの値で書き込んでも、DBには秒までが保存されることを検証する。
     */
    public function test_sub_second_component_is_truncated_on_write(): void
    {
        // Act: サブ秒（.900）付きの値で書き込む
        $careLog = CareLog::factory()->create([
            'occurred_at' => '2026-07-29 12:34:56.900',
        ]);

        // Assert: Eloquentのキャストを介さない生の値で確認する
        $this->assertSame(
            '2026-07-29 12:34:56',
            DB::table('care_logs')->where('id', $careLog->id)->value('occurred_at'),
        );
    }
}
