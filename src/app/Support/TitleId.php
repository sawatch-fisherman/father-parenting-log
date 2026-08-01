<?php

namespace App\Support;

use Database\Seeders\TitleSeeder;

/**
 * TotoOps 標準の称号マスタ（`titles`）に対する固定ID。
 *
 * `titles` は全ユーザー共通のSeeder固定マスタで、ユーザーカスタムは存在しない。
 * 称号名（`name`）としきい値（`condition_value`）は後から調整されうるため、
 * Seederの同一性キーには表示ラベルではなくこの固定IDを使う（`name`で`updateOrCreate`すると、
 * 称号名を修正した瞬間に既存行が更新されず重複行が増えてしまう）。
 *
 * ユーザーが行を作らないテーブルなので、{@see CareActionId}と違い予約域の確保は不要。
 * 定数名は変わりうる表示ラベルではなく「対象育児行動＋条件種別＋段階」で構成する。
 *
 * 値は`1`からの単純連番で、{@see TitleSeeder}の配列の並び順
 * （＝`sort_order`）と一致する。ID自体に育児行動や等級を読み取れる意味は持たせない
 * （称号の絞り込み・並べ替えは`care_action_id`・`condition_type`・`sort_order`で行えるため、
 * ID側に構造を持たせる保守コストに見合わない）。並びは以下の3ブロック：
 *
 * - `1`〜`51`：Count（累計回数系）。育児行動の表示順 × 銅・銀・金
 * - `52`〜`54`：全体Streak（`care_action_id IS NULL`）
 * - `55`〜`84`：育児行動別Streak（毎日発生しうる10行動 × 銅・銀・金）
 *
 * @see docs/data-model.md ⑥ `titles`
 * @see docs/decisions.md §1.3「ID／主キーの形式」例外規定・「称号の提示順・等級・一覧表示」
 */
final class TitleId
{
    // Count（累計回数系）— 育児行動の表示順。TIER1＝銅／TIER2＝銀／TIER3＝金。

    public const int DIAPER_CHANGE_COUNT_TIER1 = 1;

    public const int DIAPER_CHANGE_COUNT_TIER2 = 2;

    public const int DIAPER_CHANGE_COUNT_TIER3 = 3;

    public const int CHANGE_CLOTHES_COUNT_TIER1 = 4;

    public const int CHANGE_CLOTHES_COUNT_TIER2 = 5;

    public const int CHANGE_CLOTHES_COUNT_TIER3 = 6;

    public const int BRUSH_TEETH_COUNT_TIER1 = 7;

    public const int BRUSH_TEETH_COUNT_TIER2 = 8;

    public const int BRUSH_TEETH_COUNT_TIER3 = 9;

    public const int NASAL_CARE_COUNT_TIER1 = 10;

    public const int NASAL_CARE_COUNT_TIER2 = 11;

    public const int NASAL_CARE_COUNT_TIER3 = 12;

    public const int NAIL_TRIM_COUNT_TIER1 = 13;

    public const int NAIL_TRIM_COUNT_TIER2 = 14;

    public const int NAIL_TRIM_COUNT_TIER3 = 15;

    public const int READ_ALOUD_COUNT_TIER1 = 16;

    public const int READ_ALOUD_COUNT_TIER2 = 17;

    public const int READ_ALOUD_COUNT_TIER3 = 18;

    public const int INDOOR_PLAY_COUNT_TIER1 = 19;

    public const int INDOOR_PLAY_COUNT_TIER2 = 20;

    public const int INDOOR_PLAY_COUNT_TIER3 = 21;

    public const int BATH_COUNT_TIER1 = 22;

    public const int BATH_COUNT_TIER2 = 23;

    public const int BATH_COUNT_TIER3 = 24;

    public const int TOILET_TRAINING_COUNT_TIER1 = 25;

    public const int TOILET_TRAINING_COUNT_TIER2 = 26;

    public const int TOILET_TRAINING_COUNT_TIER3 = 27;

    public const int PUT_TO_SLEEP_COUNT_TIER1 = 28;

    public const int PUT_TO_SLEEP_COUNT_TIER2 = 29;

    public const int PUT_TO_SLEEP_COUNT_TIER3 = 30;

    public const int NIGHT_CRYING_COUNT_TIER1 = 31;

    public const int NIGHT_CRYING_COUNT_TIER2 = 32;

    public const int NIGHT_CRYING_COUNT_TIER3 = 33;

    public const int MILK_FEEDING_COUNT_TIER1 = 34;

    public const int MILK_FEEDING_COUNT_TIER2 = 35;

    public const int MILK_FEEDING_COUNT_TIER3 = 36;

    public const int MEAL_SUPPORT_COUNT_TIER1 = 37;

    public const int MEAL_SUPPORT_COUNT_TIER2 = 38;

    public const int MEAL_SUPPORT_COUNT_TIER3 = 39;

    public const int COMMUTE_ESCORT_COUNT_TIER1 = 40;

    public const int COMMUTE_ESCORT_COUNT_TIER2 = 41;

    public const int COMMUTE_ESCORT_COUNT_TIER3 = 42;

    public const int WALK_PARK_COUNT_TIER1 = 43;

    public const int WALK_PARK_COUNT_TIER2 = 44;

    public const int WALK_PARK_COUNT_TIER3 = 45;

    public const int OUTING_HOLD_COUNT_TIER1 = 46;

    public const int OUTING_HOLD_COUNT_TIER2 = 47;

    public const int OUTING_HOLD_COUNT_TIER3 = 48;

    public const int SICK_CARE_COUNT_TIER1 = 49;

    public const int SICK_CARE_COUNT_TIER2 = 50;

    public const int SICK_CARE_COUNT_TIER3 = 51;

    // 全体Streak（連続日数系・育児行動を問わない）。`care_action_id IS NULL`。

    public const int OVERALL_STREAK_TIER1 = 52;

    public const int OVERALL_STREAK_TIER2 = 53;

    public const int OVERALL_STREAK_TIER3 = 54;

    // 育児行動別Streak（連続日数系）— 毎日発生しうる10行動のみ。育児行動の表示順。

    public const int DIAPER_CHANGE_STREAK_TIER1 = 55;

    public const int DIAPER_CHANGE_STREAK_TIER2 = 56;

    public const int DIAPER_CHANGE_STREAK_TIER3 = 57;

    public const int BRUSH_TEETH_STREAK_TIER1 = 58;

    public const int BRUSH_TEETH_STREAK_TIER2 = 59;

    public const int BRUSH_TEETH_STREAK_TIER3 = 60;

    public const int READ_ALOUD_STREAK_TIER1 = 61;

    public const int READ_ALOUD_STREAK_TIER2 = 62;

    public const int READ_ALOUD_STREAK_TIER3 = 63;

    public const int BATH_STREAK_TIER1 = 64;

    public const int BATH_STREAK_TIER2 = 65;

    public const int BATH_STREAK_TIER3 = 66;

    public const int TOILET_TRAINING_STREAK_TIER1 = 67;

    public const int TOILET_TRAINING_STREAK_TIER2 = 68;

    public const int TOILET_TRAINING_STREAK_TIER3 = 69;

    public const int PUT_TO_SLEEP_STREAK_TIER1 = 70;

    public const int PUT_TO_SLEEP_STREAK_TIER2 = 71;

    public const int PUT_TO_SLEEP_STREAK_TIER3 = 72;

    public const int MILK_FEEDING_STREAK_TIER1 = 73;

    public const int MILK_FEEDING_STREAK_TIER2 = 74;

    public const int MILK_FEEDING_STREAK_TIER3 = 75;

    public const int MEAL_SUPPORT_STREAK_TIER1 = 76;

    public const int MEAL_SUPPORT_STREAK_TIER2 = 77;

    public const int MEAL_SUPPORT_STREAK_TIER3 = 78;

    public const int COMMUTE_ESCORT_STREAK_TIER1 = 79;

    public const int COMMUTE_ESCORT_STREAK_TIER2 = 80;

    public const int COMMUTE_ESCORT_STREAK_TIER3 = 81;

    public const int WALK_PARK_STREAK_TIER1 = 82;

    public const int WALK_PARK_STREAK_TIER2 = 83;

    public const int WALK_PARK_STREAK_TIER3 = 84;
}
