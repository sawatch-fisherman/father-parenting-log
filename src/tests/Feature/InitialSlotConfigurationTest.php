<?php

namespace Tests\Feature;

use App\Models\CareAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * `config/totoops.php` の初期おすすめ8個が Seeder 投入済みの標準育児行動と整合していることを検証する。
 *
 * しきい値と同じく未決#11で差し替える前提のリストなので、差し替え時に壊れたことをすぐ検知できるようにする。
 * 重複があると `user_slot_configs` の `UNIQUE(user_id, care_action_id)` に直結して登録処理が落ちる。
 *
 * @see docs/decisions.md §2 未決#11
 */
class InitialSlotConfigurationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_initial_slots_are_eight_distinct_standard_care_actions(): void
    {
        $this->seed();

        $careActionIds = Config::array('totoops.initial_slot_care_action_ids');

        $this->assertCount(8, $careActionIds);
        $this->assertCount(8, array_unique($careActionIds), '初期おすすめ8個に重複がある');

        $this->assertSame(8, CareAction::query()
            ->whereNull('user_id')
            ->whereIn('id', $careActionIds)
            ->count(), '初期おすすめ8個に、Seeder投入済みでない育児行動IDが含まれている');
    }
}
