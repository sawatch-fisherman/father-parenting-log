<?php

return [
    'unlocked' => '称号を獲得しました！',
    'share_to_x' => 'Xに投稿',
    'close' => '閉じる',
    'copy' => 'コピー',
    'copied' => 'コピーしました',
    'copy_failed' => 'コピーできませんでした。上の文章を選択してコピーしてください。',
    'open_x' => 'Xを開く',
    'share_tagline' => '父親育児、今日も運用中。',
    'share_hashtags' => '#TotoOps #父親育児',

    // `TitleGrantService`が組み立てる達成内容の一文（`POST /care-logs`のレスポンスに乗る）。
    // Count・Streak × 全体・育児行動別の4パターン。`:value`はしきい値（`titles.condition_value`）。
    'achievement_count_overall' => '累計育児ログ：:value回。',
    'achievement_count_action' => '累計:action：:value回。',
    'achievement_streak_overall' => ':value日連続育児ログ達成。',
    'achievement_streak_action' => ':value日連続:action達成。',
];
