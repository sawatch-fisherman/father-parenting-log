<?php

namespace Database\Seeders;

use App\Models\CareAction;
use App\Support\CareActionId;
use Illuminate\Database\Seeder;

/**
 * TotoOps 標準の育児行動17行（`user_id IS NULL`）を投入する。
 *
 * 同一性キーは表示ラベル（`name`）ではなく{@see CareActionId}の固定ID。何度実行しても
 * 17行のままになる（冪等）。`id`は`Fillable`に含めない（Phase 2のカスタム育児行動作成で
 * クライアントが主キーを指定できてしまうのを防ぐため）ので、Seederからは`forceFill()`で明示的に代入する。
 *
 * 配列の並びがそのまま`sort_order`（S4「その他」一覧とピン留め設定画面の表示順）になる。
 * `docs/features.md`のカテゴリ（日常ケア／食事系／外出・移動／対応・耐久）順に並べているため、
 * `id`の昇順とは一致しない。`id`は永続化された主キーなので並び替えても変更しないこと。
 *
 * @see docs/features.md「育児行動一覧（基本8個の選定候補プール）」
 * @see docs/data-model.md ③ `care_actions`
 */
class CareActionSeeder extends Seeder
{
    /**
     * アプリケーションのデータベースにシードデータを投入する。
     */
    public function run(): void
    {
        $careActions = [
            // 日常ケア（起床から就寝までの流れに沿って並べる）
            [CareActionId::DIAPER_CHANGE, 'おむつ交換'],
            [CareActionId::CHANGE_CLOTHES, '着替え'],
            [CareActionId::BRUSH_TEETH, '歯磨き'],
            [CareActionId::NASAL_CARE, '鼻水ケア・鼻吸い'],
            [CareActionId::NAIL_TRIM, '爪切り'],
            [CareActionId::READ_ALOUD, '本の読み聞かせ'],
            [CareActionId::INDOOR_PLAY, '遊び相手（室内）'],
            [CareActionId::BATH, 'お風呂'],
            [CareActionId::TOILET_TRAINING, 'トイレ補助・トイトレ'],
            [CareActionId::PUT_TO_SLEEP, '寝かしつけ'],
            [CareActionId::NIGHT_CRYING, '夜泣き対応'],
            // 食事系
            [CareActionId::MILK_FEEDING, 'ミルク・授乳補助'],
            [CareActionId::MEAL_SUPPORT, '離乳食・食事補助'],
            // 外出・移動
            [CareActionId::COMMUTE_ESCORT, '送迎（保育園・習い事等）'],
            [CareActionId::WALK_PARK, '散歩・公園遊び'],
            // 対応・耐久
            [CareActionId::OUTING_HOLD, '外出中の抱っこ'],
            [CareActionId::SICK_CARE, '発熱・看病・通院'],
        ];

        foreach ($careActions as $sortOrder => [$id, $name]) {
            $careAction = CareAction::query()->find($id) ?? new CareAction;

            // 標準行はIDを明示採番するため、この保存に限り自動採番を無効化する。有効なままだと
            // Eloquent が INSERT 後に `LAST_INSERT_ID()` を読みに行き、MySQL はAUTO_INCREMENT値を
            // 生成しなかった場合に 0 を返すので、保存後のモデルの `id` が 0 に化ける。
            $careAction->incrementing = false;

            $careAction->forceFill([
                'id' => $id,
                'user_id' => null,
                'name' => $name,
                'sort_order' => $sortOrder + 1,
            ])->save();
        }
    }
}
