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

    public function test_sub_second_component_is_truncated_on_write(): void
    {
        $careLog = CareLog::factory()->create([
            'occurred_at' => '2026-07-29 12:34:56.900',
        ]);

        $this->assertSame(
            '2026-07-29 12:34:56',
            DB::table('care_logs')->where('id', $careLog->id)->value('occurred_at'),
        );
    }
}
