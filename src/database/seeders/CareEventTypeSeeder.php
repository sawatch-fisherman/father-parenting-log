<?php

namespace Database\Seeders;

use App\Models\CareEventType;
use App\Support\CareEventTypeId;
use Illuminate\Database\Seeder;

/**
 * TotoOps 標準の育児イベント種別17行（`user_id IS NULL`）を投入する。
 *
 * 同一性キーは表示ラベル（`name`）ではなく{@see CareEventTypeId}の固定ULID。何度実行しても
 * 17行のままになる（冪等）。`id`は`Fillable`に含めない（Phase 2のカスタム種別作成で
 * クライアントが主キーを指定できてしまうのを防ぐため）ので、Seederからは`forceFill()`で明示的に代入する。
 *
 * @see docs/features.md「育児イベント種別一覧（基本8個の選定候補プール）」
 * @see docs/data-model.md ③ `care_event_types`
 */
class CareEventTypeSeeder extends Seeder
{
    /**
     * アプリケーションのデータベースにシードデータを投入する。
     */
    public function run(): void
    {
        $types = [
            [CareEventTypeId::DIAPER_CHANGE, 'おむつ交換'],
            [CareEventTypeId::CHANGE_CLOTHES, '着替え'],
            [CareEventTypeId::BRUSH_TEETH, '歯磨き'],
            [CareEventTypeId::NASAL_CARE, '鼻水ケア・鼻吸い'],
            [CareEventTypeId::NAIL_TRIM, '爪切り'],
            [CareEventTypeId::READ_ALOUD, '本の読み聞かせ'],
            [CareEventTypeId::MILK_FEEDING, 'ミルク・授乳補助'],
            [CareEventTypeId::INDOOR_PLAY, '遊び相手（室内）'],
            [CareEventTypeId::BATH, 'お風呂'],
            [CareEventTypeId::COMMUTE_ESCORT, '送迎（保育園・習い事等）'],
            [CareEventTypeId::MEAL_SUPPORT, '離乳食・食事補助'],
            [CareEventTypeId::TOILET_TRAINING, 'トイレ補助・トイトレ'],
            [CareEventTypeId::WALK_PARK, '散歩・公園遊び'],
            [CareEventTypeId::PUT_TO_SLEEP, '寝かしつけ'],
            [CareEventTypeId::OUTING_HOLD, '外出中の抱っこ'],
            [CareEventTypeId::SICK_CARE, '発熱・看病・通院'],
            [CareEventTypeId::NIGHT_CRYING, '夜泣き対応'],
        ];

        foreach ($types as $sortOrder => [$id, $name]) {
            $careEventType = CareEventType::query()->find($id) ?? new CareEventType;

            $careEventType->forceFill([
                'id' => $id,
                'user_id' => null,
                'name' => $name,
                'sort_order' => $sortOrder + 1,
            ])->save();
        }
    }
}
