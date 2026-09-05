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
 * 値は`1`からの単純連番で、{@see TitleSeeder}の配列の並び順（＝`sort_order`）と一致する。
 * 並び順は「**対象範囲（全体→育児行動別）→ 条件種別（Count→Streak）→
 * 育児行動の表示順 → 等級（銅→銀→金）**」の4段階で決まり、ブロックは以下の4つ：
 *
 * - `1`〜`3`：Count・全体合計（`care_action_id IS NULL`）
 * - `4`〜`6`：Streak・全体（`care_action_id IS NULL`）
 * - `7`〜`57`：Count・育児行動別（17行動 × 銅・銀・金）
 * - `58`〜`93`：Streak・育児行動別（毎日発生しうる12行動 × 銅・銀・金）
 *
 * 全体称号を先頭に置くのは、Phase 2 の称号図鑑（93件の一覧）で件数の少ない総論を先に見せるため。
 * 図鑑は「獲得済み＋各系統の未獲得最小しきい値1件」方式なので、登録直後のユーザーが最初に見る
 * 目標が全ユーザー共通の全体称号になる（先頭が育児行動別だと、その父親の子どもの年齢によっては
 * 的外れな目標が最初に出てしまう）。
 *
 * ID自体に育児行動や等級を読み取れる意味は持たせない
 * （称号の絞り込み・並べ替えは`care_action_id`・`condition_type`・`sort_order`で行えるため、
 * ID側に構造を持たせる保守コストに見合わない）。ブロックごとに番号を区切る予約域も設けない：
 * 図鑑の`ORDER BY`が使うのは`sort_order`だけで`id`は登場せず、`id`に並び順の意図を焼き込むと
 * 下記のとおり不変化してしまい、かえって並べ替えの自由度が落ちるため。
 *
 * **`id`と`sort_order`の一致が保てるのは未リリースの間だけ**：`titles.id`は`user_titles.title_id`
 * から`ON DELETE RESTRICT`で参照される永続的な主キーなので、獲得済みの称号が1件でも存在したら
 * 二度と振り直せない。以降に称号を追加する場合は`id`を末尾に採番するしかなく、
 * 提示順を保つなら{@see TitleSeeder}の配列には正しい位置に差し込むことになるため、
 * `id`と`sort_order`は一致しなくなる。**優先するのは常に`sort_order`側**
 * （`id`は同一性キーにすぎず、ユーザーの目に触れるのは提示順だけのため）。
 *
 * @see docs/data-model.md ⑥ `titles`
 * @see docs/decisions.md §1.3「ID／主キーの形式」例外規定・「称号の提示順・等級・一覧表示」
 */
final class TitleId
{
    // Count（全体合計）— 育児行動を問わない累計回数。`care_action_id IS NULL`。
    // カスタム育児行動（Phase 2以降）の積み上げを受け止める唯一のCount系統でもある。

    public const int OVERALL_COUNT_TIER1 = 1;

    public const int OVERALL_COUNT_TIER2 = 2;

    public const int OVERALL_COUNT_TIER3 = 3;

    // Streak（全体）— 育児行動を問わず記録した日が連続。`care_action_id IS NULL`。

    public const int OVERALL_STREAK_TIER1 = 4;

    public const int OVERALL_STREAK_TIER2 = 5;

    public const int OVERALL_STREAK_TIER3 = 6;

    // Count（育児行動別）— 育児行動の表示順。TIER1＝銅／TIER2＝銀／TIER3＝金。

    public const int DIAPER_CHANGE_COUNT_TIER1 = 7;

    public const int DIAPER_CHANGE_COUNT_TIER2 = 8;

    public const int DIAPER_CHANGE_COUNT_TIER3 = 9;

    public const int CHANGE_CLOTHES_COUNT_TIER1 = 10;

    public const int CHANGE_CLOTHES_COUNT_TIER2 = 11;

    public const int CHANGE_CLOTHES_COUNT_TIER3 = 12;

    public const int BRUSH_TEETH_COUNT_TIER1 = 13;

    public const int BRUSH_TEETH_COUNT_TIER2 = 14;

    public const int BRUSH_TEETH_COUNT_TIER3 = 15;

    public const int NASAL_CARE_COUNT_TIER1 = 16;

    public const int NASAL_CARE_COUNT_TIER2 = 17;

    public const int NASAL_CARE_COUNT_TIER3 = 18;

    public const int NAIL_TRIM_COUNT_TIER1 = 19;

    public const int NAIL_TRIM_COUNT_TIER2 = 20;

    public const int NAIL_TRIM_COUNT_TIER3 = 21;

    public const int READ_ALOUD_COUNT_TIER1 = 22;

    public const int READ_ALOUD_COUNT_TIER2 = 23;

    public const int READ_ALOUD_COUNT_TIER3 = 24;

    public const int INDOOR_PLAY_COUNT_TIER1 = 25;

    public const int INDOOR_PLAY_COUNT_TIER2 = 26;

    public const int INDOOR_PLAY_COUNT_TIER3 = 27;

    public const int BATH_COUNT_TIER1 = 28;

    public const int BATH_COUNT_TIER2 = 29;

    public const int BATH_COUNT_TIER3 = 30;

    public const int TOILET_TRAINING_COUNT_TIER1 = 31;

    public const int TOILET_TRAINING_COUNT_TIER2 = 32;

    public const int TOILET_TRAINING_COUNT_TIER3 = 33;

    public const int PUT_TO_SLEEP_COUNT_TIER1 = 34;

    public const int PUT_TO_SLEEP_COUNT_TIER2 = 35;

    public const int PUT_TO_SLEEP_COUNT_TIER3 = 36;

    public const int NIGHT_CRYING_COUNT_TIER1 = 37;

    public const int NIGHT_CRYING_COUNT_TIER2 = 38;

    public const int NIGHT_CRYING_COUNT_TIER3 = 39;

    public const int MILK_FEEDING_COUNT_TIER1 = 40;

    public const int MILK_FEEDING_COUNT_TIER2 = 41;

    public const int MILK_FEEDING_COUNT_TIER3 = 42;

    public const int MEAL_SUPPORT_COUNT_TIER1 = 43;

    public const int MEAL_SUPPORT_COUNT_TIER2 = 44;

    public const int MEAL_SUPPORT_COUNT_TIER3 = 45;

    public const int COMMUTE_ESCORT_COUNT_TIER1 = 46;

    public const int COMMUTE_ESCORT_COUNT_TIER2 = 47;

    public const int COMMUTE_ESCORT_COUNT_TIER3 = 48;

    public const int WALK_PARK_COUNT_TIER1 = 49;

    public const int WALK_PARK_COUNT_TIER2 = 50;

    public const int WALK_PARK_COUNT_TIER3 = 51;

    public const int OUTING_HOLD_COUNT_TIER1 = 52;

    public const int OUTING_HOLD_COUNT_TIER2 = 53;

    public const int OUTING_HOLD_COUNT_TIER3 = 54;

    public const int SICK_CARE_COUNT_TIER1 = 55;

    public const int SICK_CARE_COUNT_TIER2 = 56;

    public const int SICK_CARE_COUNT_TIER3 = 57;

    // Streak（育児行動別）— 毎日発生しうる12行動のみ。育児行動の表示順。

    public const int DIAPER_CHANGE_STREAK_TIER1 = 58;

    public const int DIAPER_CHANGE_STREAK_TIER2 = 59;

    public const int DIAPER_CHANGE_STREAK_TIER3 = 60;

    public const int CHANGE_CLOTHES_STREAK_TIER1 = 61;

    public const int CHANGE_CLOTHES_STREAK_TIER2 = 62;

    public const int CHANGE_CLOTHES_STREAK_TIER3 = 63;

    public const int BRUSH_TEETH_STREAK_TIER1 = 64;

    public const int BRUSH_TEETH_STREAK_TIER2 = 65;

    public const int BRUSH_TEETH_STREAK_TIER3 = 66;

    public const int READ_ALOUD_STREAK_TIER1 = 67;

    public const int READ_ALOUD_STREAK_TIER2 = 68;

    public const int READ_ALOUD_STREAK_TIER3 = 69;

    public const int INDOOR_PLAY_STREAK_TIER1 = 70;

    public const int INDOOR_PLAY_STREAK_TIER2 = 71;

    public const int INDOOR_PLAY_STREAK_TIER3 = 72;

    public const int BATH_STREAK_TIER1 = 73;

    public const int BATH_STREAK_TIER2 = 74;

    public const int BATH_STREAK_TIER3 = 75;

    public const int TOILET_TRAINING_STREAK_TIER1 = 76;

    public const int TOILET_TRAINING_STREAK_TIER2 = 77;

    public const int TOILET_TRAINING_STREAK_TIER3 = 78;

    public const int PUT_TO_SLEEP_STREAK_TIER1 = 79;

    public const int PUT_TO_SLEEP_STREAK_TIER2 = 80;

    public const int PUT_TO_SLEEP_STREAK_TIER3 = 81;

    public const int MILK_FEEDING_STREAK_TIER1 = 82;

    public const int MILK_FEEDING_STREAK_TIER2 = 83;

    public const int MILK_FEEDING_STREAK_TIER3 = 84;

    public const int MEAL_SUPPORT_STREAK_TIER1 = 85;

    public const int MEAL_SUPPORT_STREAK_TIER2 = 86;

    public const int MEAL_SUPPORT_STREAK_TIER3 = 87;

    public const int COMMUTE_ESCORT_STREAK_TIER1 = 88;

    public const int COMMUTE_ESCORT_STREAK_TIER2 = 89;

    public const int COMMUTE_ESCORT_STREAK_TIER3 = 90;

    public const int WALK_PARK_STREAK_TIER1 = 91;

    public const int WALK_PARK_STREAK_TIER2 = 92;

    public const int WALK_PARK_STREAK_TIER3 = 93;
}
