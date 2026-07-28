<?php

namespace Database\Seeders;

use App\Enums\TitleConditionType;
use App\Models\Title;
use App\Support\CareEventTypeId;
use Illuminate\Database\Seeder;

/**
 * TotoOps 標準の称号マスタを投入する。
 *
 * `condition_value`（しきい値）は未決#4のため暫定値。実際の育児で使ってから確定する。
 *
 * @see docs/features.md「称号の例」
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
            [CareEventTypeId::DIAPER_CHANGE, 'おむつ交換士 Lv.1', TitleConditionType::Count, 10],
            [CareEventTypeId::DIAPER_CHANGE, 'おむつ交換士 Lv.5', TitleConditionType::Count, 100],
            [CareEventTypeId::PUT_TO_SLEEP, '寝かしつけ見習い', TitleConditionType::Count, 10],
            [CareEventTypeId::PUT_TO_SLEEP, '寝かしつけ職人', TitleConditionType::Count, 100],
            [CareEventTypeId::NIGHT_CRYING, '夜間対応班', TitleConditionType::Count, 10],
            [CareEventTypeId::NIGHT_CRYING, '深夜オペレーター', TitleConditionType::Count, 50],
            [CareEventTypeId::NIGHT_CRYING, '夜泣きインシデント司令官', TitleConditionType::Count, 100],
            [CareEventTypeId::BATH, 'お風呂担当大臣', TitleConditionType::Count, 50],
            [CareEventTypeId::OUTING_HOLD, '抱っこ耐久型パパ', TitleConditionType::Count, 50],
            // Streak（連続日数系）
            [null, '3日連続育児ログ', TitleConditionType::Streak, 3],
            [CareEventTypeId::DIAPER_CHANGE, '7日連続おむつ交換', TitleConditionType::Streak, 7],
        ];

        foreach ($titles as $sortOrder => [$careEventTypeId, $name, $conditionType, $conditionValue]) {
            Title::query()->updateOrCreate(
                ['name' => $name],
                [
                    'care_event_type_id' => $careEventTypeId,
                    'condition_type' => $conditionType,
                    // TODO: 未決#4（しきい値）暫定値。実際の育児で使ってから確定する。
                    'condition_value' => $conditionValue,
                    'sort_order' => $sortOrder + 1,
                ],
            );
        }
    }
}
