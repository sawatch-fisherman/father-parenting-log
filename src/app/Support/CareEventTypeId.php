<?php

namespace App\Support;

/**
 * TotoOps 標準の育児イベント種別（`care_event_types.user_id IS NULL` の17行）に対する固定ULID。
 *
 * 書式は`0`＋接頭辞`STD`＋ゼロ埋め連番22桁の計26文字（Crockford Base32のみ使用）。
 *
 * 注意：ここでの固定IDは大文字だが、`HasUlids::newUniqueId()` が生成するULIDは小文字のため、
 * `care_event_types.id` には大文字と小文字のULIDが混在する。予約接頭辞`0STD`のガード
 * （docs/data-model.md ③ の Phase 2 要件）や PHP 側での比較を書く際は、
 * 必ず大小文字を無視すること（例：`str_starts_with(strtoupper($id), '0STD')`）。
 * 素の `str_starts_with($id, '0STD')` は小文字の生成値に対して機能しない。
 *
 * @see docs/data-model.md ③ `care_event_types`
 * @see docs/decisions.md §1.3「ID／主キーの UUID 化」例外規定
 */
final class CareEventTypeId
{
    public const string DIAPER_CHANGE = '0STD0000000000000000000001';

    public const string CHANGE_CLOTHES = '0STD0000000000000000000002';

    public const string BRUSH_TEETH = '0STD0000000000000000000003';

    public const string NASAL_CARE = '0STD0000000000000000000004';

    public const string NAIL_TRIM = '0STD0000000000000000000005';

    public const string READ_ALOUD = '0STD0000000000000000000006';

    public const string MILK_FEEDING = '0STD0000000000000000000007';

    public const string INDOOR_PLAY = '0STD0000000000000000000008';

    public const string BATH = '0STD0000000000000000000009';

    public const string COMMUTE_ESCORT = '0STD0000000000000000000010';

    public const string MEAL_SUPPORT = '0STD0000000000000000000011';

    public const string TOILET_TRAINING = '0STD0000000000000000000012';

    public const string WALK_PARK = '0STD0000000000000000000013';

    public const string PUT_TO_SLEEP = '0STD0000000000000000000014';

    public const string OUTING_HOLD = '0STD0000000000000000000015';

    public const string SICK_CARE = '0STD0000000000000000000016';

    public const string NIGHT_CRYING = '0STD0000000000000000000017';
}
