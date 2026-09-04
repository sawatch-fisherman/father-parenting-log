<?php

namespace App\Http\Controllers;

use App\Models\CareLog;
use App\Models\User;
use App\Support\StatsBucketWindow;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
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

        $tab = StatsBucketWindow::resolveTab($this->queryString($request, 'tab'));

        if ($tab === 'all') {
            return Inertia::render('Stats/Index', [
                'tab' => 'all',
                'baseDate' => null,
                'period' => null,
                'allTime' => $this->buildAllTimeStats($user),
            ]);
        }

        $baseDate = StatsBucketWindow::resolveBaseDate($this->queryString($request, 'base_date'));
        $window = StatsBucketWindow::resolve($tab, $baseDate);

        return Inertia::render('Stats/Index', [
            'tab' => $tab,
            'baseDate' => $baseDate->toDateString(),
            'period' => $this->buildPeriodStats($user, $window),
            'allTime' => null,
        ]);
    }

    /**
     * クエリパラメータを文字列としてのみ受け取る（配列で送られた場合は`null`扱いにする）。
     *
     * `?tab[]=day`のように配列で送られると`Request::query()`はPHP配列を返し、`?string`型を
     * 要求する`StatsBucketWindow`側で`TypeError`になり500になってしまう。「不正な値は既定へ
     * フォールバックする」という`StatsBucketWindow`の責務を配列入力にも及ばせるため、ここで
     * スカラー文字列以外を`null`に正規化してから渡す。
     */
    private function queryString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) ? $value : null;
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
     * `care_logs`は高頻度で増える前提のログテーブルのため（docs/decisions.md §1.3「ログテーブルと
     * マスタテーブルの分離」）、全件をモデルとしてハイドレートせずDB側の集約クエリ（`COUNT`・
     * `GROUP BY`）で済ませる。育児行動ごとの累計は多い順に並べる。個人内の育児タスク種別ランキングは
     * 「比較しない」原則の例外として明示的に許容されている（CLAUDE.md「ブレさせてはいけない線引き」）。
     *
     * @return array{totalCount: int, totalDays: int, monthlyCumulative: list<array{label: string, cumulativeTotal: int}>, careActionTotals: list<array{careActionId: int, name: string, total: int}>, hasRecords: bool}
     */
    private function buildAllTimeStats(User $user): array
    {
        $totalCount = $user->careLogs()->count();

        if ($totalCount === 0) {
            return [
                'totalCount' => 0,
                'totalDays' => 0,
                'monthlyCumulative' => [],
                'careActionTotals' => [],
                'hasRecords' => false,
            ];
        }

        $totalDays = (int) $user->careLogs()
            ->selectRaw('COUNT(DISTINCT DATE(occurred_at)) as total')
            ->value('total');

        $monthlyCumulative = $this->monthlyCumulative($user);

        /** @var list<array{careActionId: int, name: string, total: int}> $careActionTotals */
        $careActionTotals = $user->careLogs()
            ->selectRaw('care_action_id, COUNT(*) as total')
            ->groupBy('care_action_id')
            ->orderByDesc('total')
            ->with('careAction:id,name')
            ->get()
            ->map(fn (CareLog $row): array => [
                'careActionId' => $row->care_action_id,
                'name' => (string) $row->careAction?->name,
                'total' => (int) $row->getAttribute('total'),
            ])
            ->all();

        return [
            'totalCount' => $totalCount,
            'totalDays' => $totalDays,
            'monthlyCumulative' => $monthlyCumulative,
            'careActionTotals' => $careActionTotals,
            'hasRecords' => true,
        ];
    }

    /**
     * 記録開始月から今月まで、1か月きざみで累計記録数を積み上げる（wireframes.md S12全期間タブ）。
     *
     * 月別件数はDBから記録のある月ぶんだけ返るため、記録の無い月は直前の累計値を持ち越して埋め、
     * 今月に記録が無くても今月ぶんまでちょうど延ばす（累計折れ線の傾き＝記録のペースを保つため、
     * 記録の空白期間を軸から欠落させない）。
     *
     * @return list<array{label: string, cumulativeTotal: int}>
     */
    private function monthlyCumulative(User $user): array
    {
        // `DATE_FORMAT()`はMySQL方言でテスト環境（`sqlite`, `:memory:`）には存在しないため、
        // ドライバごとに正しい構文を出し分ける（PHPStanのliteral-string要求を満たすため、
        // 動的に組み立てず選択肢をそのままリテラルとして書く）。
        $selectRaw = match (DB::connection()->getDriverName()) {
            'sqlite' => "strftime('%Y-%m', occurred_at) as month, COUNT(*) as total",
            default => "DATE_FORMAT(occurred_at, '%Y-%m') as month, COUNT(*) as total",
        };

        /** @var Collection<string, int> $monthlyTotals */
        $monthlyTotals = $user->careLogs()
            ->selectRaw($selectRaw)
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('total', 'month');

        $firstMonthKey = $monthlyTotals->keys()->first();

        if ($firstMonthKey === null) {
            return [];
        }

        $cursor = Carbon::parse($firstMonthKey.'-01')->startOfMonth();
        $lastMonth = Carbon::now()->startOfMonth();

        $cumulative = 0;
        $monthlyCumulative = [];

        while ($cursor->lte($lastMonth)) {
            $key = $cursor->format('Y-m');
            $cumulative += (int) ($monthlyTotals->get($key) ?? 0);
            $monthlyCumulative[] = ['label' => $key, 'cumulativeTotal' => $cumulative];
            $cursor->addMonth();
        }

        return $monthlyCumulative;
    }
}
