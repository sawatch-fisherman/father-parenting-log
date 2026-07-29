<?php

namespace App\Support;

/**
 * TotoOps 標準の称号マスタ（`titles`）に対する固定ULID。
 *
 * `titles` は全ユーザー共通のSeeder固定マスタで、ユーザーカスタムは存在しない。
 * 称号名（`name`）としきい値（`condition_value`）はどちらも未決#4で差し替えられる前提のため、
 * Seederの同一性キーには表示ラベルではなくこの固定IDを使う（`name`で`updateOrCreate`すると、
 * 称号名を修正した瞬間に既存行が更新されず重複行が増えてしまう）。
 *
 * 書式は{@see CareEventTypeId}と同じ規則で、接頭辞は`TTS`（TotoOps standard TitleS）。
 * 定数名は変わりうる表示ラベルではなく「対象種別＋条件種別＋段階」で構成する。
 *
 * @see docs/data-model.md ⑥ `titles`
 * @see docs/decisions.md §1.3「ID／主キーの UUID 化」例外規定・§2 未決#4
 */
final class TitleId
{
    public const string DIAPER_CHANGE_COUNT_TIER1 = '0TTS0000000000000000000001';

    public const string DIAPER_CHANGE_COUNT_TIER2 = '0TTS0000000000000000000002';

    public const string PUT_TO_SLEEP_COUNT_TIER1 = '0TTS0000000000000000000003';

    public const string PUT_TO_SLEEP_COUNT_TIER2 = '0TTS0000000000000000000004';

    public const string NIGHT_CRYING_COUNT_TIER1 = '0TTS0000000000000000000005';

    public const string NIGHT_CRYING_COUNT_TIER2 = '0TTS0000000000000000000006';

    public const string NIGHT_CRYING_COUNT_TIER3 = '0TTS0000000000000000000007';

    public const string BATH_COUNT_TIER1 = '0TTS0000000000000000000008';

    public const string OUTING_HOLD_COUNT_TIER1 = '0TTS0000000000000000000009';

    public const string OVERALL_STREAK_TIER1 = '0TTS0000000000000000000010';

    public const string DIAPER_CHANGE_STREAK_TIER1 = '0TTS0000000000000000000011';
}
