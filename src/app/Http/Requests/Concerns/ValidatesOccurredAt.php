<?php

namespace App\Http\Requests\Concerns;

use App\Support\CareLogWindow;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Date;

/**
 * `occurred_at` の許容範囲（「`backdate_days`日前の00:00 〜 `now() + 5分`」）の検証ルールと
 * エラー文言を、`StoreCareLogRequest`（M4）と `UpdateCareLogRequest`（M6）で共有する。
 *
 * 範囲そのものは両者で完全に同じ（[docs/implementation-plan.md](../../../../../docs/implementation-plan.md)
 * 「M6 履歴」）で、片方だけ直すと「登録はできるが編集は弾かれる」ような食い違いが生まれるため、
 * `CareLogWindow` が遡り境界の算出を1箇所に集約しているのと同じ理由でここに寄せる。
 * 一方で重複チェック（`Rule::unique`）は共有しない：編集時だけ自分自身を `ignore()` する必要があり、
 * 条件が異なるため各リクエスト側で個別に定義する。
 */
trait ValidatesOccurredAt
{
    /**
     * `occurred_at` の許容範囲ルールを返す。
     */
    protected function occurredAtRangeRule(): Date
    {
        // `->format()`を`betweenOrEqual()`より先に呼ぶ必要がある：`Rule::date`は
        // Carbonインスタンスを既定で`Y-m-d`（時刻を捨てる）にフォーマットするため、
        // 先に秒精度のフォーマットを指定しておかないと「now()+5分」の上限が
        // 「今日中ならいつでも可」まで緩んでしまう。
        return Rule::date()->format('Y-m-d H:i:s')->betweenOrEqual(
            CareLogWindow::backdateFloor(),
            now()->addMinutes(5)
        );
    }

    /**
     * `occurred_at` の範囲・重複エラーに割り当てる文言を返す。
     *
     * @return array<string, string>
     */
    protected function occurredAtMessages(): array
    {
        return [
            // `:days`は`config('totoops.care_log.backdate_days')`をそのまま渡す。文言に直書きすると
            // 設定値を変えたときにUI・エラー文言だけ古い日数のまま残ってしまうため
            // （`CareLogWindow`が遡り境界の算出を1箇所に集約しているのと同じ理由）。
            'occurred_at.after_or_equal' => __('validation.care_log_occurred_at_too_old', [
                'days' => Config::integer('totoops.care_log.backdate_days'),
            ]),
            'occurred_at.before_or_equal' => __('validation.care_log_occurred_at_future'),
            'occurred_at.unique' => __('validation.care_log_occurred_at_duplicate'),
        ];
    }
}
