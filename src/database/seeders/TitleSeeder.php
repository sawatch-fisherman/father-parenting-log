<?php

namespace Database\Seeders;

use App\Enums\TitleConditionType;
use App\Enums\TitleGrade;
use App\Models\Title;
use App\Support\CareActionId;
use App\Support\TitleId;
use Illuminate\Database\Seeder;

/**
 * TotoOps 標準の称号マスタ84行を投入する。
 *
 * 同一性キーには`name`ではなく{@see TitleId}の固定IDを使う（`titles.name`は非ユニーク列で、
 * 称号名を修正して再シードすると既存行が更新されず重複行が増えてしまうため）。
 *
 * 称号名は、Count系が「{育児行動の短縮ラベル}＋見習い／職人／名人」で、接尾辞と等級（銅／銀／金）が
 * 1対1に対応する。称号名だけで等級が読み取れる状態を保つこと。Streak系は「{日数表記}連続{短縮ラベル}」で、
 * 名前にしきい値が埋め込まれるため`condition_value`を変えるときは`name`も併せて変える。
 *
 * しきい値は、Count系が育児行動を発生頻度で3帯に分けて帯ごとに共通の値を当てる
 * （高頻度 50/200/500・中頻度 20/100/300・低頻度 10/30/100）。この頻度帯は`care_actions.sort_order`の
 * カテゴリ（日常ケア等）とは独立した軸で、両者は一致しない。Streak系は全体版が 7/30/90日、
 * 育児行動別版が 3/7/30日（同じ等級でも育児行動別のほうが難度が高いため1段階易しくする）。
 *
 * 育児行動別Streakは「毎日発生しうる育児行動」10個のみを対象とする。爪切りや発熱・看病のように
 * 連続日数が原理的に伸びない育児行動には設定しない。
 *
 * @see docs/features.md「称号一覧」
 * @see docs/data-model.md ⑥ `titles`
 * @see docs/decisions.md §1.3「称号の提示順・等級・一覧表示」
 */
class TitleSeeder extends Seeder
{
    /**
     * アプリケーションのデータベースにシードデータを投入する。
     */
    public function run(): void
    {
        $titles = [
            // Count（累計回数系）— 育児行動の表示順。
            [TitleId::DIAPER_CHANGE_COUNT_TIER1, CareActionId::DIAPER_CHANGE, 'おむつ交換見習い', TitleGrade::Bronze, TitleConditionType::Count, 50],
            [TitleId::DIAPER_CHANGE_COUNT_TIER2, CareActionId::DIAPER_CHANGE, 'おむつ交換職人', TitleGrade::Silver, TitleConditionType::Count, 200],
            [TitleId::DIAPER_CHANGE_COUNT_TIER3, CareActionId::DIAPER_CHANGE, 'おむつ交換名人', TitleGrade::Gold, TitleConditionType::Count, 500],
            [TitleId::CHANGE_CLOTHES_COUNT_TIER1, CareActionId::CHANGE_CLOTHES, '着替え見習い', TitleGrade::Bronze, TitleConditionType::Count, 50],
            [TitleId::CHANGE_CLOTHES_COUNT_TIER2, CareActionId::CHANGE_CLOTHES, '着替え職人', TitleGrade::Silver, TitleConditionType::Count, 200],
            [TitleId::CHANGE_CLOTHES_COUNT_TIER3, CareActionId::CHANGE_CLOTHES, '着替え名人', TitleGrade::Gold, TitleConditionType::Count, 500],
            [TitleId::BRUSH_TEETH_COUNT_TIER1, CareActionId::BRUSH_TEETH, '歯磨き見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::BRUSH_TEETH_COUNT_TIER2, CareActionId::BRUSH_TEETH, '歯磨き職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::BRUSH_TEETH_COUNT_TIER3, CareActionId::BRUSH_TEETH, '歯磨き名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::NASAL_CARE_COUNT_TIER1, CareActionId::NASAL_CARE, '鼻吸い見習い', TitleGrade::Bronze, TitleConditionType::Count, 10],
            [TitleId::NASAL_CARE_COUNT_TIER2, CareActionId::NASAL_CARE, '鼻吸い職人', TitleGrade::Silver, TitleConditionType::Count, 30],
            [TitleId::NASAL_CARE_COUNT_TIER3, CareActionId::NASAL_CARE, '鼻吸い名人', TitleGrade::Gold, TitleConditionType::Count, 100],
            [TitleId::NAIL_TRIM_COUNT_TIER1, CareActionId::NAIL_TRIM, '爪切り見習い', TitleGrade::Bronze, TitleConditionType::Count, 10],
            [TitleId::NAIL_TRIM_COUNT_TIER2, CareActionId::NAIL_TRIM, '爪切り職人', TitleGrade::Silver, TitleConditionType::Count, 30],
            [TitleId::NAIL_TRIM_COUNT_TIER3, CareActionId::NAIL_TRIM, '爪切り名人', TitleGrade::Gold, TitleConditionType::Count, 100],
            [TitleId::READ_ALOUD_COUNT_TIER1, CareActionId::READ_ALOUD, '読み聞かせ見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::READ_ALOUD_COUNT_TIER2, CareActionId::READ_ALOUD, '読み聞かせ職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::READ_ALOUD_COUNT_TIER3, CareActionId::READ_ALOUD, '読み聞かせ名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::INDOOR_PLAY_COUNT_TIER1, CareActionId::INDOOR_PLAY, '室内遊び見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::INDOOR_PLAY_COUNT_TIER2, CareActionId::INDOOR_PLAY, '室内遊び職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::INDOOR_PLAY_COUNT_TIER3, CareActionId::INDOOR_PLAY, '室内遊び名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::BATH_COUNT_TIER1, CareActionId::BATH, 'お風呂見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::BATH_COUNT_TIER2, CareActionId::BATH, 'お風呂職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::BATH_COUNT_TIER3, CareActionId::BATH, 'お風呂名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::TOILET_TRAINING_COUNT_TIER1, CareActionId::TOILET_TRAINING, 'トイトレ見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::TOILET_TRAINING_COUNT_TIER2, CareActionId::TOILET_TRAINING, 'トイトレ職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::TOILET_TRAINING_COUNT_TIER3, CareActionId::TOILET_TRAINING, 'トイトレ名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::PUT_TO_SLEEP_COUNT_TIER1, CareActionId::PUT_TO_SLEEP, '寝かしつけ見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::PUT_TO_SLEEP_COUNT_TIER2, CareActionId::PUT_TO_SLEEP, '寝かしつけ職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::PUT_TO_SLEEP_COUNT_TIER3, CareActionId::PUT_TO_SLEEP, '寝かしつけ名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::NIGHT_CRYING_COUNT_TIER1, CareActionId::NIGHT_CRYING, '夜泣き対応見習い', TitleGrade::Bronze, TitleConditionType::Count, 10],
            [TitleId::NIGHT_CRYING_COUNT_TIER2, CareActionId::NIGHT_CRYING, '夜泣き対応職人', TitleGrade::Silver, TitleConditionType::Count, 30],
            [TitleId::NIGHT_CRYING_COUNT_TIER3, CareActionId::NIGHT_CRYING, '夜泣き対応名人', TitleGrade::Gold, TitleConditionType::Count, 100],
            [TitleId::MILK_FEEDING_COUNT_TIER1, CareActionId::MILK_FEEDING, 'ミルク見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::MILK_FEEDING_COUNT_TIER2, CareActionId::MILK_FEEDING, 'ミルク職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::MILK_FEEDING_COUNT_TIER3, CareActionId::MILK_FEEDING, 'ミルク名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::MEAL_SUPPORT_COUNT_TIER1, CareActionId::MEAL_SUPPORT, '食事補助見習い', TitleGrade::Bronze, TitleConditionType::Count, 50],
            [TitleId::MEAL_SUPPORT_COUNT_TIER2, CareActionId::MEAL_SUPPORT, '食事補助職人', TitleGrade::Silver, TitleConditionType::Count, 200],
            [TitleId::MEAL_SUPPORT_COUNT_TIER3, CareActionId::MEAL_SUPPORT, '食事補助名人', TitleGrade::Gold, TitleConditionType::Count, 500],
            [TitleId::COMMUTE_ESCORT_COUNT_TIER1, CareActionId::COMMUTE_ESCORT, '送迎見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::COMMUTE_ESCORT_COUNT_TIER2, CareActionId::COMMUTE_ESCORT, '送迎職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::COMMUTE_ESCORT_COUNT_TIER3, CareActionId::COMMUTE_ESCORT, '送迎名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::WALK_PARK_COUNT_TIER1, CareActionId::WALK_PARK, '公園遊び見習い', TitleGrade::Bronze, TitleConditionType::Count, 20],
            [TitleId::WALK_PARK_COUNT_TIER2, CareActionId::WALK_PARK, '公園遊び職人', TitleGrade::Silver, TitleConditionType::Count, 100],
            [TitleId::WALK_PARK_COUNT_TIER3, CareActionId::WALK_PARK, '公園遊び名人', TitleGrade::Gold, TitleConditionType::Count, 300],
            [TitleId::OUTING_HOLD_COUNT_TIER1, CareActionId::OUTING_HOLD, '抱っこ見習い', TitleGrade::Bronze, TitleConditionType::Count, 10],
            [TitleId::OUTING_HOLD_COUNT_TIER2, CareActionId::OUTING_HOLD, '抱っこ職人', TitleGrade::Silver, TitleConditionType::Count, 30],
            [TitleId::OUTING_HOLD_COUNT_TIER3, CareActionId::OUTING_HOLD, '抱っこ名人', TitleGrade::Gold, TitleConditionType::Count, 100],
            [TitleId::SICK_CARE_COUNT_TIER1, CareActionId::SICK_CARE, '看病見習い', TitleGrade::Bronze, TitleConditionType::Count, 10],
            [TitleId::SICK_CARE_COUNT_TIER2, CareActionId::SICK_CARE, '看病職人', TitleGrade::Silver, TitleConditionType::Count, 30],
            [TitleId::SICK_CARE_COUNT_TIER3, CareActionId::SICK_CARE, '看病名人', TitleGrade::Gold, TitleConditionType::Count, 100],
            // 全体Streak（連続日数系・育児行動を問わない）。
            [TitleId::OVERALL_STREAK_TIER1, null, '1週間連続育児ログ', TitleGrade::Bronze, TitleConditionType::Streak, 7],
            [TitleId::OVERALL_STREAK_TIER2, null, '1ヶ月連続育児ログ', TitleGrade::Silver, TitleConditionType::Streak, 30],
            [TitleId::OVERALL_STREAK_TIER3, null, '3ヶ月連続育児ログ', TitleGrade::Gold, TitleConditionType::Streak, 90],
            // 育児行動別Streak（連続日数系）— 毎日発生しうる10行動のみ。
            [TitleId::DIAPER_CHANGE_STREAK_TIER1, CareActionId::DIAPER_CHANGE, '3日連続おむつ交換', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::DIAPER_CHANGE_STREAK_TIER2, CareActionId::DIAPER_CHANGE, '1週間連続おむつ交換', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::DIAPER_CHANGE_STREAK_TIER3, CareActionId::DIAPER_CHANGE, '1ヶ月連続おむつ交換', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::BRUSH_TEETH_STREAK_TIER1, CareActionId::BRUSH_TEETH, '3日連続歯磨き', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::BRUSH_TEETH_STREAK_TIER2, CareActionId::BRUSH_TEETH, '1週間連続歯磨き', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::BRUSH_TEETH_STREAK_TIER3, CareActionId::BRUSH_TEETH, '1ヶ月連続歯磨き', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::READ_ALOUD_STREAK_TIER1, CareActionId::READ_ALOUD, '3日連続読み聞かせ', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::READ_ALOUD_STREAK_TIER2, CareActionId::READ_ALOUD, '1週間連続読み聞かせ', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::READ_ALOUD_STREAK_TIER3, CareActionId::READ_ALOUD, '1ヶ月連続読み聞かせ', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::BATH_STREAK_TIER1, CareActionId::BATH, '3日連続お風呂', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::BATH_STREAK_TIER2, CareActionId::BATH, '1週間連続お風呂', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::BATH_STREAK_TIER3, CareActionId::BATH, '1ヶ月連続お風呂', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::TOILET_TRAINING_STREAK_TIER1, CareActionId::TOILET_TRAINING, '3日連続トイトレ', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::TOILET_TRAINING_STREAK_TIER2, CareActionId::TOILET_TRAINING, '1週間連続トイトレ', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::TOILET_TRAINING_STREAK_TIER3, CareActionId::TOILET_TRAINING, '1ヶ月連続トイトレ', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::PUT_TO_SLEEP_STREAK_TIER1, CareActionId::PUT_TO_SLEEP, '3日連続寝かしつけ', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::PUT_TO_SLEEP_STREAK_TIER2, CareActionId::PUT_TO_SLEEP, '1週間連続寝かしつけ', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::PUT_TO_SLEEP_STREAK_TIER3, CareActionId::PUT_TO_SLEEP, '1ヶ月連続寝かしつけ', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::MILK_FEEDING_STREAK_TIER1, CareActionId::MILK_FEEDING, '3日連続ミルク', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::MILK_FEEDING_STREAK_TIER2, CareActionId::MILK_FEEDING, '1週間連続ミルク', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::MILK_FEEDING_STREAK_TIER3, CareActionId::MILK_FEEDING, '1ヶ月連続ミルク', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::MEAL_SUPPORT_STREAK_TIER1, CareActionId::MEAL_SUPPORT, '3日連続食事補助', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::MEAL_SUPPORT_STREAK_TIER2, CareActionId::MEAL_SUPPORT, '1週間連続食事補助', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::MEAL_SUPPORT_STREAK_TIER3, CareActionId::MEAL_SUPPORT, '1ヶ月連続食事補助', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::COMMUTE_ESCORT_STREAK_TIER1, CareActionId::COMMUTE_ESCORT, '3日連続送迎', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::COMMUTE_ESCORT_STREAK_TIER2, CareActionId::COMMUTE_ESCORT, '1週間連続送迎', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::COMMUTE_ESCORT_STREAK_TIER3, CareActionId::COMMUTE_ESCORT, '1ヶ月連続送迎', TitleGrade::Gold, TitleConditionType::Streak, 30],
            [TitleId::WALK_PARK_STREAK_TIER1, CareActionId::WALK_PARK, '3日連続公園遊び', TitleGrade::Bronze, TitleConditionType::Streak, 3],
            [TitleId::WALK_PARK_STREAK_TIER2, CareActionId::WALK_PARK, '1週間連続公園遊び', TitleGrade::Silver, TitleConditionType::Streak, 7],
            [TitleId::WALK_PARK_STREAK_TIER3, CareActionId::WALK_PARK, '1ヶ月連続公園遊び', TitleGrade::Gold, TitleConditionType::Streak, 30],
        ];

        foreach ($titles as $sortOrder => [$id, $careActionId, $name, $grade, $conditionType, $conditionValue]) {
            $title = Title::query()->find($id) ?? new Title;

            // IDを明示採番するため、この保存に限り自動採番を無効化する（理由は CareActionSeeder 参照）。
            $title->incrementing = false;

            $title->forceFill([
                'id' => $id,
                'care_action_id' => $careActionId,
                'name' => $name,
                'grade' => $grade,
                'condition_type' => $conditionType,
                'condition_value' => $conditionValue,
                'sort_order' => $sortOrder + 1,
            ])->save();
        }
    }
}
