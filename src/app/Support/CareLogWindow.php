<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * 育児ログの遡り操作（作成・`occurred_at`変更・削除）の許容下限を1箇所に集約する。
 *
 * 「`backdate_days`日前の00:00」を返す。`now()->subDays(backdate_days)`（時刻を保持した
 * まま日付だけ引く）とは異なる点に注意する（丸1日分ズレうる）。`StoreCareLogRequest`・
 * `UpdateCareLogRequest`（M6）・`CareLogPolicy`（M6）・S10の日付ピッカー範囲・S13の
 * 「…」非活性判定（M6）は、個別に日付計算を書かずすべてこのクラスを参照する。
 *
 * 例：今日が2026-08-21、`backdate_days=7`、現在時刻が23:50の場合
 *   - `backdateFloor()`（実装）        → 2026-08-14 00:00（日付境界）
 *   - `now()->subDays(7)`（NG・不採用） → 2026-08-14 23:50（2026-08-14のほぼ丸1日分が遡り不可になる）
 *
 * @see docs/decisions.md §1.3「育児ログの遡り操作は直近7日に制限する」
 */
final class CareLogWindow
{
    /**
     * 遡り操作の許容下限（`backdate_days`日前の00:00）を返す。
     */
    public static function backdateFloor(): Carbon
    {
        return Carbon::today()->subDays(Config::integer('totoops.care_log.backdate_days'));
    }
}
