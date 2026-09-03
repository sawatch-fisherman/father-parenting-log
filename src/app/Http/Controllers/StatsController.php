<?php

namespace App\Http\Controllers;

use App\Models\CareLog;
use App\Models\User;
use App\Support\StatsBucketWindow;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * S12（期間別集計画面）の表示を担当する。
 *
 * 自分の `care_logs` を直接集計する（Phase 2 の集約テーブルは使わない。docs/decisions.md §1.3）。
 */
class StatsController extends Controller
{
    /**
     * 日/週/月タブは基準日を含む7バケットぶんの集計、全期間タブは累計実績を表示する。
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $tab = StatsBucketWindow::resolveTab($request->query('tab'));

        if ($tab === 'all') {
            return Inertia::render('Stats/Index', [
                'tab' => 'all',
                'baseDate' => null,
                'period' => null,
                'allTime' => $this->buildAllTimeStats($user),
            ]);
        }

        $baseDate = StatsBucketWindow::resolveBaseDate($request->query('base_date'));
        $window = StatsBucketWindow::resolve($tab, $baseDate);

        return Inertia::render('Stats/Index', [
            'tab' => $tab,
            'baseDate' => $baseDate->toDateString(),
            'period' => $this->buildPeriodStats($user, $window),
            'allTime' => null,
        ]);
    }

    /**
     * 7バケットぶんの積み上げ棒グラフ用データ（バケット合計・育児行動ごとの内訳）を組み立てる。
     *
     * 系列（`series`）にはその期間に記録のある育児行動だけを含め、`sort_order` 昇順に並べる
     * （docs/decisions.md §1.3「色は care_action_id に固定割り当てする」。並び順そのものは色の
     * 決定には使わないが、他画面と揃えるため一覧の並びだけ `sort_order` を踏襲する）。
     *
     * @param  array{buckets: list<array{start: Carbon, end: Carbon}>, prevBaseDate: Carbon, nextBaseDate: Carbon}  $window
     * @return array{buckets: list<array{start: string, end: string, total: int}>, series: list<array{careActionId: int, name: string, counts: list<int>}>, hasRecords: bool, prevBaseDate: string, nextBaseDate: string}
     */
    private function buildPeriodStats(User $user, array $window): array
    {
        $buckets = $window['buckets'];
        $windowStart = $buckets[0]['start'];
        $windowEnd = $buckets[StatsBucketWindow::BUCKET_COUNT - 1]['end'];

        /** @var Collection<int, CareLog> $logs */
        $logs = $user->careLogs()
            ->whereBetween('occurred_at', [$windowStart, $windowEnd])
            ->with('careAction:id,name,sort_order')
            ->get(['care_action_id', 'occurred_at']);

        // care_action_id => [bucketIndex => count]
        $countsByAction = [];
        // care_action_id => ['name' => ..., 'sortOrder' => ...]（内訳の並び順に使う。5.5節の色indexには使わない）
        $actionMeta = [];

        foreach ($logs as $log) {
            $bucketIndex = $this->bucketIndexFor($log->occurred_at, $buckets);

            if ($bucketIndex === null) {
                continue;
            }

            $actionId = $log->care_action_id;
            $countsByAction[$actionId][$bucketIndex] = ($countsByAction[$actionId][$bucketIndex] ?? 0) + 1;
            $actionMeta[$actionId] ??= [
                'name' => (string) $log->careAction?->name,
                'sortOrder' => $log->careAction === null ? 0 : $log->careAction->sort_order,
            ];
        }

        uasort($actionMeta, fn (array $a, array $b): int => $a['sortOrder'] <=> $b['sortOrder']);

        $series = [];
        foreach ($actionMeta as $actionId => $meta) {
            $counts = [];
            for ($i = 0; $i < StatsBucketWindow::BUCKET_COUNT; $i++) {
                $counts[] = $countsByAction[$actionId][$i] ?? 0;
            }
            $series[] = [
                'careActionId' => $actionId,
                'name' => $meta['name'],
                'counts' => $counts,
            ];
        }

        $bucketTotals = array_fill(0, StatsBucketWindow::BUCKET_COUNT, 0);
        foreach ($countsByAction as $counts) {
            foreach ($counts as $bucketIndex => $count) {
                $bucketTotals[$bucketIndex] += $count;
            }
        }

        $bucketData = [];
        foreach ($buckets as $index => $bucket) {
            $bucketData[] = [
                'start' => $bucket['start']->toDateString(),
                'end' => $bucket['end']->toDateString(),
                'total' => $bucketTotals[$index],
            ];
        }

        return [
            'buckets' => $bucketData,
            'series' => $series,
            'hasRecords' => $logs->isNotEmpty(),
            'prevBaseDate' => $window['prevBaseDate']->toDateString(),
            'nextBaseDate' => $window['nextBaseDate']->toDateString(),
        ];
    }

    /**
     * 育児ログの実施日時が、7バケットのうちどのインデックス（0〜6）に属するかを返す（該当なしはnull）。
     *
     * `CareLog::$occurred_at`（`Carbon\Carbon`）と`StatsBucketWindow`が組み立てるバケット境界
     * （`Illuminate\Support\Carbon`）は実装クラスが異なるため、共通の`CarbonInterface`で受ける。
     *
     * @param  list<array{start: Carbon, end: Carbon}>  $buckets
     */
    private function bucketIndexFor(CarbonInterface $occurredAt, array $buckets): ?int
    {
        foreach ($buckets as $index => $bucket) {
            if ($occurredAt->between($bucket['start'], $bucket['end'])) {
                return $index;
            }
        }

        return null;
    }

    /**
     * 全期間タブの累計実績（累計記録数・記録日数・月別累計・育児行動ごとの累計）を組み立てる。
     *
     * 育児行動ごとの累計は多い順に並べる。個人内の育児タスク種別ランキングは「比較しない」原則の
     * 例外として明示的に許容されている（CLAUDE.md「ブレさせてはいけない線引き」）。
     *
     * @return array{totalCount: int, totalDays: int, monthlyCumulative: list<array{label: string, cumulativeTotal: int}>, careActionTotals: list<array{careActionId: int, name: string, total: int}>, hasRecords: bool}
     */
    private function buildAllTimeStats(User $user): array
    {
        /** @var Collection<int, CareLog> $logs */
        $logs = $user->careLogs()
            ->with('careAction:id,name,sort_order')
            ->orderBy('occurred_at')
            ->get(['care_action_id', 'occurred_at']);

        if ($logs->isEmpty()) {
            return [
                'totalCount' => 0,
                'totalDays' => 0,
                'monthlyCumulative' => [],
                'careActionTotals' => [],
                'hasRecords' => false,
            ];
        }

        $totalDays = $logs
            ->map(fn (CareLog $log): string => $log->occurred_at->toDateString())
            ->unique()
            ->count();

        // by-ref変数をCollection::map()の中で累積すると、PHPStanが`$cumulative`の型を
        // `int|float`まで広げてしまい戻り値の型（int）と一致しなくなるため、素直なforeachで組み立てる。
        $monthlyTotals = $logs->groupBy(fn (CareLog $log): string => $log->occurred_at->format('Y-m'));

        $cumulative = 0;
        $monthlyCumulative = [];
        foreach ($monthlyTotals as $month => $group) {
            $cumulative += $group->count();
            $monthlyCumulative[] = ['label' => (string) $month, 'cumulativeTotal' => $cumulative];
        }

        $careActionTotals = $logs
            ->groupBy('care_action_id')
            ->map(fn (Collection $group, int $actionId): array => [
                'careActionId' => $actionId,
                'name' => (string) $group->first()?->careAction?->name,
                'total' => $group->count(),
            ])
            ->sortByDesc('total')
            ->all();
        $careActionTotals = array_values($careActionTotals);

        return [
            'totalCount' => $logs->count(),
            'totalDays' => $totalDays,
            'monthlyCumulative' => $monthlyCumulative,
            'careActionTotals' => $careActionTotals,
            'hasRecords' => true,
        ];
    }
}
