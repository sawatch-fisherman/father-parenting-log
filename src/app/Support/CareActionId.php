<?php

namespace App\Support;

/**
 * TotoOps 標準の育児行動（`care_actions.user_id IS NULL` の17行）に対する固定ID。
 *
 * Seeder実行前は自動採番のIDが存在せず、`config/totoops.php`（初期おすすめ8個）や
 * `TitleSeeder` が標準行を名指しする手段が `name`（表示ラベル。後から変更されうる）の
 * 文字列一致しかなくなってしまうため、標準17行だけは固定IDを明示採番する。
 *
 * 予約域（`1` 〜 {@see self::CUSTOM_ID_FLOOR} - 1）とユーザーカスタム行の分離は
 * `care_actions` のAUTO_INCREMENTカウンタでDB側が保証しており、アプリ層のガードは不要
 * （Phase 2 でカスタム育児行動の作成を実装する際も同様）。
 *
 * @see docs/data-model.md ③ `care_actions`
 * @see docs/decisions.md §1.3「ID／主キーの形式」例外規定
 */
final class CareActionId
{
    /**
     * ユーザーカスタム行の採番開始値。これ未満は TotoOps 標準行のための予約域。
     *
     * 標準行の追加余地として広めに取ってある（現在17行）。育児行動は
     * 「父親主体のみ」の選定原則で絞られるため、この域を使い切ることはない。
     */
    public const int CUSTOM_ID_FLOOR = 1000;

    public const int DIAPER_CHANGE = 1;

    public const int CHANGE_CLOTHES = 2;

    public const int BRUSH_TEETH = 3;

    public const int NASAL_CARE = 4;

    public const int NAIL_TRIM = 5;

    public const int READ_ALOUD = 6;

    public const int MILK_FEEDING = 7;

    public const int INDOOR_PLAY = 8;

    public const int BATH = 9;

    public const int COMMUTE_ESCORT = 10;

    public const int MEAL_SUPPORT = 11;

    public const int TOILET_TRAINING = 12;

    public const int WALK_PARK = 13;

    public const int PUT_TO_SLEEP = 14;

    public const int OUTING_HOLD = 15;

    public const int SICK_CARE = 16;

    public const int NIGHT_CRYING = 17;
}
