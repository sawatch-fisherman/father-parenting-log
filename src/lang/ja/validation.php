<?php

return [
    'required' => ':attributeを入力してください。',
    'string' => ':attributeは文字列で入力してください。',
    'integer' => ':attributeは整数で入力してください。',
    'date' => ':attributeは日時の形式で入力してください。',
    'date_format' => ':attributeは日時の形式で入力してください。',
    'exists' => '選択された:attributeは無効です。',
    'max' => [
        'string' => ':attributeは:max文字以内で入力してください。',
        // `UpdateSlotConfigRequest`の`slots`（ピン留めは最大8個）用。
        'array' => ':attributeは:max個以内にしてください。',
    ],
    'between' => [
        // `UpdateSlotConfigRequest`の`slots.*.slot_position`（1〜8）用。
        'numeric' => ':attributeは:min〜:maxの範囲で入力してください。',
    ],
    // `UpdateSlotConfigRequest`の`slots.*.slot_position`／`slots.*.care_action_id`
    // （重複禁止）用。
    'distinct' => ':attributeが重複しています。',
    'enum' => '選択された:attributeは無効です。',
    // `LocaleController@update` の `Rule::in(config('totoops.supported_locales'))` 用。
    // 定義しないと、対応外のロケールを送られたときに翻訳キー（`validation.in`）がそのまま画面に出る。
    'in' => '選択された:attributeは無効です。',

    // `Concerns\ValidatesOccurredAt`（`StoreCareLogRequest`・`UpdateCareLogRequest` が併用）が
    // `messages()` 経由で `occurred_at.*` に個別に割り当てる。
    // 汎用の `:date` テンプレートではなく利用者向けの分かりやすい文言にするため専用キーにしている。
    // `:days` は `config('totoops.care_log.backdate_days')` を渡す（`CareLogWindow`同様、
    // 遡り日数のハードコードを避けて設定値と二重管理にならないようにするため）。
    'care_log_occurred_at_too_old' => '記録できるのは:days日前までです。',
    // 短タップは端末のローカル時計をそのまま送信するため、端末の時刻設定がズレている・
    // タイムゾーンがJSTでない場合もこのエラーになりうる。原因（対処法）まで示す。
    'care_log_occurred_at_future' => '未来の日時は記録できません。端末の時刻設定をご確認ください。',
    'care_log_occurred_at_duplicate' => '同じ日時に同じ記録があります。',

    'attributes' => [
        'locale' => '表示言語',
        'nickname' => 'ニックネーム',
        'age_group' => '年代',
        'child_age_group' => 'いちばん下のお子さんの年齢帯',
        'care_action_id' => '育児行動',
        'occurred_at' => '実施日時',
        'memo' => 'メモ',
        'slots' => 'ピン留め',
        'slots.*.slot_position' => 'スロット位置',
        'slots.*.care_action_id' => '育児行動',
    ],
];
