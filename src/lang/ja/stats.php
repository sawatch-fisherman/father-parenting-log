<?php

return [
    'title' => '集計',

    'tab_day' => '日',
    'tab_week' => '週',
    'tab_month' => '月',
    'tab_all' => '全期間',

    'prev_period' => '前の期間',
    'next_period' => '次の期間',
    // 育児ログは未来日時に存在しえないため、最新の期間より先へは進めない（非活性の「次」を
    // タップしたときのトースト）。責める文面にしない（docs/wireframes.md S13の考え方を踏襲）。
    'next_period_locked_toast' => 'これより先の期間はまだ記録できません',

    // 内訳マトリクス表・累計リスト共通の見出し（DESIGN.md 5.5節：色チップ＋育児行動名で凡例を兼ねる）。
    'breakdown_action_header' => '育児行動',
    'breakdown_total_label' => '合計',
    // 内訳マトリクスの`role="table"`ラッパに付けるアクセシブルな名前（スクリーンリーダー向け）。
    'breakdown_table_label' => '育児行動ごとの記録内訳',

    // 全期間タブの累計実績カード。
    'all_time_total_count' => '累計記録',
    'all_time_total_days' => '記録した日数',
    'count_unit' => ':count回',
    'days_unit' => ':days日',

    'empty_title' => 'まだ記録がありません',
    'empty_body' => '記録画面からはじめましょう',
    'empty_cta' => '記録する',
];
