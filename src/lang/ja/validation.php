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
    ],
    'enum' => '選択された:attributeは無効です。',

    // `StoreCareLogRequest`（M4）が `messages()` で `occurred_at.*` に個別に割り当てる。
    // 汎用の `:date` テンプレートではなく利用者向けの分かりやすい文言にするため専用キーにしている。
    'care_log_occurred_at_too_old' => '記録できるのは7日前までです。',
    'care_log_occurred_at_future' => '未来の日時は記録できません。',
    'care_log_occurred_at_duplicate' => '同じ日時に同じ記録があります。',

    'attributes' => [
        'locale' => '表示言語',
        'nickname' => 'ニックネーム',
        'age_group' => '年代',
        'child_age_group' => 'いちばん下のお子さんの年齢帯',
        'care_action_id' => '育児行動',
        'occurred_at' => '実施日時',
        'memo' => 'メモ',
    ],
];
