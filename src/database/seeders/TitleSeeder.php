<?php

namespace Database\Seeders;

use App\Enums\TitleConditionType;
use App\Models\Title;
use App\Support\CareActionId;
use App\Support\TitleId;
use Illuminate\Database\Seeder;

/**
 * TotoOps 標準の称号マスタを投入する。
 *
 * `name`（称号名）と`condition_value`（しきい値）は未決#4のため暫定値で、後から差し替える前提。
 * そのため同一性キーには`name`ではなく{@see TitleId}の固定IDを使う（`titles.name`は非ユニーク列で、
 * 称号名を修正して再シードすると既存行が更新されず重複行が増えてしまうため）。
 *
 * @see docs/features.md「称号の例」
 * @see docs/data-model.md ⑥ `titles`
 * @see docs/decisions.md §2 未決#4
 */
class TitleSeeder extends Seeder
{
    /**
     * アプリケーションのデータベースにシードデータを投入する。
     */
    public function run(): void
    {
        $titles = [
            // Count（累計回数系）
            [TitleId::DIAPER_CHANGE_COUNT_TIER1, CareActionId::DIAPER_CHANGE, 'おむつ交換士 Lv.1', TitleConditionType::Count, 10],
            [TitleId::DIAPER_CHANGE_COUNT_TIER2, CareActionId::DIAPER_CHANGE, 'おむつ交換士 Lv.5', TitleConditionType::Count, 100],
            [TitleId::PUT_TO_SLEEP_COUNT_TIER1, CareActionId::PUT_TO_SLEEP, '寝かしつけ見習い', TitleConditionType::Count, 10],
            [TitleId::PUT_TO_SLEEP_COUNT_TIER2, CareActionId::PUT_TO_SLEEP, '寝かしつけ職人', TitleConditionType::Count, 100],
            [TitleId::NIGHT_CRYING_COUNT_TIER1, CareActionId::NIGHT_CRYING, '夜間対応班', TitleConditionType::Count, 10],
            [TitleId::NIGHT_CRYING_COUNT_TIER2, CareActionId::NIGHT_CRYING, '深夜オペレーター', TitleConditionType::Count, 50],
            [TitleId::NIGHT_CRYING_COUNT_TIER3, CareActionId::NIGHT_CRYING, '夜泣きインシデント司令官', TitleConditionType::Count, 100],
            [TitleId::BATH_COUNT_TIER1, CareActionId::BATH, 'お風呂担当大臣', TitleConditionType::Count, 50],
            [TitleId::OUTING_HOLD_COUNT_TIER1, CareActionId::OUTING_HOLD, '抱っこ耐久型パパ', TitleConditionType::Count, 50],
            // Streak（連続日数系）
            [TitleId::OVERALL_STREAK_TIER1, null, '3日連続育児ログ', TitleConditionType::Streak, 3],
            [TitleId::DIAPER_CHANGE_STREAK_TIER1, CareActionId::DIAPER_CHANGE, '7日連続おむつ交換', TitleConditionType::Streak, 7],
        ];

        foreach ($titles as $sortOrder => [$id, $careActionId, $name, $conditionType, $conditionValue]) {
            $title = Title::query()->find($id) ?? new Title;

            // IDを明示採番するため、この保存に限り自動採番を無効化する（理由は CareActionSeeder 参照）。
            $title->incrementing = false;

            $title->forceFill([
                'id' => $id,
                'care_action_id' => $careActionId,
                // TODO: 未決#4（称号名・しきい値）暫定値。実際の育児で使ってから確定する。
                'name' => $name,
                'condition_type' => $conditionType,
                'condition_value' => $conditionValue,
                'sort_order' => $sortOrder + 1,
            ])->save();
        }
    }
}
