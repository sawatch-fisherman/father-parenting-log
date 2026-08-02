<?php

use App\Support\CareActionId;

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

    'initial_slot_care_action_ids' => [
        CareActionId::DIAPER_CHANGE,
        CareActionId::MILK_FEEDING,
        CareActionId::BATH,
        CareActionId::PUT_TO_SLEEP,
        CareActionId::NIGHT_CRYING,
        CareActionId::CHANGE_CLOTHES,
        CareActionId::WALK_PARK,
        CareActionId::READ_ALOUD,
    ],

    /*
    |--------------------------------------------------------------------------
    | 育児ログ（care logs）
    |--------------------------------------------------------------------------
    |
    | backdate_days: 育児ログを遡って操作できる日数。`occurred_at` が
    | 「backdate_days 日前の 00:00」より前の記録は、作成・日時変更・削除の
    | いずれもできない（締め＝closing period）。
    |
    | ハードコードせず config 値にしているのは、サービス障害や長期メンテで
    | 正当な記録が失われた場合に、運用側が一時的に窓を広げる逃げ道を残すため。
    | 恒久的に広げると `aggregate_*` の確定済み集計を開き直す必要が生じる。
    |
    | @see docs/decisions.md §1.3「育児ログの遡り操作は直近7日に制限する」
    |
    */

    'care_log' => [
        'backdate_days' => 7,
    ],

];
