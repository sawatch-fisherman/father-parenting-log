<?php

use App\Support\CareEventTypeId;

return [

    /*
    |--------------------------------------------------------------------------
    | サポートするロケール（supported locales）
    |--------------------------------------------------------------------------
    |
    | SetLocale ミドルウェア（cookie 判定）と LocaleController のバリデーションが
    | 共通で参照する唯一の定義。両者に別々のリストを書くとズレるため1箇所に寄せる。
    | 先頭要素が既定ロケール（`config('app.locale')` と一致させること）。
    | 未翻訳キーは `fallback_locale`（ja）へフォールバックする。
    |
    | @see docs/decisions.md §1.3「多言語化（i18n）」
    |
    */

    'supported_locales' => ['ja', 'en'],

    /*
    |--------------------------------------------------------------------------
    | 初期おすすめ8個（common recommended slots）
    |--------------------------------------------------------------------------
    |
    | プロフィール登録完了時に user_slot_configs へ自動作成する、常時8アイコンの初期ピン留め。
    | TODO: 未決#11。実際の育児で使ってから確定する（暫定リスト）。
    |
    */

    'initial_slot_care_event_type_ids' => [
        CareEventTypeId::DIAPER_CHANGE,
        CareEventTypeId::MILK_FEEDING,
        CareEventTypeId::BATH,
        CareEventTypeId::PUT_TO_SLEEP,
        CareEventTypeId::NIGHT_CRYING,
        CareEventTypeId::CHANGE_CLOTHES,
        CareEventTypeId::WALK_PARK,
        CareEventTypeId::READ_ALOUD,
    ],

];
