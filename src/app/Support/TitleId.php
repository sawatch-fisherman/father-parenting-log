<?php

namespace App\Support;

/**
 * TotoOps 標準の称号マスタ（`titles`）に対する固定ID。
 *
 * `titles` は全ユーザー共通のSeeder固定マスタで、ユーザーカスタムは存在しない。
 * 称号名（`name`）としきい値（`condition_value`）はどちらも未決#4で差し替えられる前提のため、
 * Seederの同一性キーには表示ラベルではなくこの固定IDを使う（`name`で`updateOrCreate`すると、
 * 称号名を修正した瞬間に既存行が更新されず重複行が増えてしまう）。
 *
 * ユーザーが行を作らないテーブルなので、{@see CareActionId}と違い予約域の確保は不要。
 * 定数名は変わりうる表示ラベルではなく「対象育児行動＋条件種別＋段階」で構成する。
 *
 * @see docs/data-model.md ⑥ `titles`
 * @see docs/decisions.md §1.3「ID／主キーの形式」例外規定・§2 未決#4
 */
final class TitleId
{
    public const int DIAPER_CHANGE_COUNT_TIER1 = 1;

    public const int DIAPER_CHANGE_COUNT_TIER2 = 2;

    public const int PUT_TO_SLEEP_COUNT_TIER1 = 3;

    public const int PUT_TO_SLEEP_COUNT_TIER2 = 4;

    public const int NIGHT_CRYING_COUNT_TIER1 = 5;

    public const int NIGHT_CRYING_COUNT_TIER2 = 6;

    public const int NIGHT_CRYING_COUNT_TIER3 = 7;

    public const int BATH_COUNT_TIER1 = 8;

    public const int OUTING_HOLD_COUNT_TIER1 = 9;

    public const int OVERALL_STREAK_TIER1 = 10;

    public const int DIAPER_CHANGE_STREAK_TIER1 = 11;
}
