<?php

namespace App\Http\Controllers;

use App\Models\CareLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Inertia\Inertia;
use Inertia\Response;

/**
 * S13（記録履歴画面・タイムライン）の表示を担当する。
 *
 * 育児ログの編集・削除は `CareLogController`（S11）が担当し、この画面は一覧と導線のみを持つ。
 */
class HistoryController extends Controller
{
    /**
     * 自分の育児ログを日付ごとにグループ化し、新しい順のタイムラインとして表示する。
     *
     * 各行の `editable` は「遡り操作の締め（`backdate_days`日前の00:00）より後か」で、
     * S13の「…」の活性／非活性がサーバー側の `CareLogPolicy` と必ず同じ境界になるよう、
     * 認可と同じ `CareLog::isWithinBackdateWindow()` の結果をそのまま渡す（クライアント側で
     * 日付計算をやり直すと1日ズレて「操作できるのに保存できない」行が生まれる。
     * docs/decisions.md §1.3）。
     *
     * 表示件数の上限・ページングは未決#23のため、暫定で全件を返す
     * （docs/implementation-plan.md「M6 履歴」ブロッカー）。
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        /** @var Collection<int, CareLog> $careLogs */
        $careLogs = $user->careLogs()
            ->with('careAction:id,name')
            // 同一時刻に別々の育児行動を記録できる（UNIQUEは`care_action_id`も含む）ため、
            // `occurred_at`だけでは並びが一意に定まらない。`id`（ULID＝生成順に単調増加）を
            // 第2キーにして、再読み込みのたびに順序が入れ替わらないようにする。
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->get();

        $days = $careLogs
            ->groupBy(fn (CareLog $careLog): string => $careLog->occurred_at->toDateString())
            ->map(fn (Collection $logsOfDay, string $date): array => [
                // 見出しの表記（「2026年7月15日」等）はロケール依存のため、サーバーではISO日付の
                // まま渡してVue側の`Intl.DateTimeFormat`で整形する（`lang/*`にも二重に持たない）。
                'date' => $date,
                'logs' => $logsOfDay
                    ->map(fn (CareLog $careLog): array => [
                        'id' => $careLog->id,
                        'time' => $careLog->occurred_at->format('H:i'),
                        'careActionName' => $careLog->careAction?->name,
                        'memo' => $careLog->memo,
                        'editable' => $careLog->isWithinBackdateWindow(),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values()
            ->all();

        return Inertia::render('History/Index', [
            'days' => $days,
            // 非活性の「…」をタップしたときのトースト文言（「:days日を過ぎた記録は…」）に渡す。
            'backdateDays' => Config::integer('totoops.care_log.backdate_days'),
        ]);
    }
}
