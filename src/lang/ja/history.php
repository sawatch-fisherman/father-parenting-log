<?php

return [
    'title' => '履歴',
    'empty_title' => 'まだ記録がありません',
    'empty_body' => '記録画面からはじめましょう',
    'empty_cta' => '記録する',

    // 各行の「…」（ケバブメニュー）のアクセシブルな名前。記号だけでは読み上げ時に
    // 何のボタンか分からないため、行の内容（時刻と育児行動名）を含めて組み立てる。
    'edit_menu' => ':time :name を変更・削除する',
    'locked_menu' => ':time :name は変更できません',
    // 非活性の「…」をタップしたときのトースト。責める文面にしない（docs/wireframes.md S13）。
    'locked_toast' => ':days日を過ぎた記録は変更できません',
];
