<script setup lang="ts">
// Inertia::render('Stats/Index')（StatsController@index）が読み込むS12のページ。
// 日/週/月タブは基準日を含む7バケットぶんの積み上げ棒＋内訳マトリクス、全期間タブは
// 累計折れ線＋累計実績カードを表示する（docs/decisions.md §1.3「S12 集計グラフの仕様」）。
import { Link } from '@inertiajs/vue3';
import { BarElement, CategoryScale, Chart, LinearScale, LineElement, type Plugin, PointElement, Tooltip } from 'chart.js';
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useCareActionSeriesColor } from '@/composables/useCareActionSeriesColor';
import { useToast } from '@/composables/useToast';
import { useTrans } from '@/composables/useTrans';

// Chart.jsはコアの標準機能のみで描く（docs/decisions.md §1.3）。`registerables`（全要素）は登録せず、
// 日/週/月タブの積み上げ棒と全期間タブの折れ線が使う要素だけ登録する。凡例（Legend）は置かない
// 仕様（DESIGN.md 5.5節）のため登録しない。
// （内訳：CategoryScale＝X軸、LinearScale＝Y軸、BarElement＝棒、LineElement/PointElement＝折れ線と点、Tooltip＝ホバー時の吹き出し）
Chart.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Tooltip);

// Canvas内だけ別フォントになるのを防ぐ（DESIGN.md 6.1節・implementation-plan.md M7備考）。
// `Chart.defaults.font.family`（軸ラベル等）と`bucketTotalPlugin`の`ctx.font`（バケット合計ラベル）の
// 両方でこの定数を使い回し、Canvas内で1箇所だけフォントが食い違う事態を防ぐ。
const CHART_FONT_FAMILY = "'Noto Sans JP', -apple-system, BlinkMacSystemFont, 'Hiragino Kaku Gothic ProN', sans-serif";
Chart.defaults.font.family = CHART_FONT_FAMILY;

type Tab = 'day' | 'week' | 'month' | 'all';

// 日/週/月タブの棒グラフ1本（1バケット）分のデータ。
interface PeriodBucket {
    start: string;
    end: string;
    total: number;
}

// 日/週/月タブの内訳1行（育児行動1つ）分のデータ。
interface PeriodSeries {
    careActionId: number;
    name: string;
    counts: number[];
}

// 日/週/月タブ（`StatsController::buildPeriodStats()`）が返すデータ全体。
interface PeriodStats {
    buckets: PeriodBucket[];
    series: PeriodSeries[];
    hasRecords: boolean;
    prevBaseDate: string;
    nextBaseDate: string;
    // 直近バケットが既に今日を含む＝これより先は育児ログが存在しえない期間（`StatsBucketWindow::resolve()`）。
    // trueのとき「次」の期間送りを非活性にする。
    atLatestPeriod: boolean;
}

// 全期間タブの累計折れ線グラフの1点（1か月）分のデータ。
interface AllTimeMonthly {
    label: string;
    cumulativeTotal: number;
}

// 全期間タブの育児行動別累計ランキング1行分のデータ。
interface AllTimeActionTotal {
    careActionId: number;
    name: string;
    total: number;
}

// 全期間タブ（`StatsController::buildAllTimeStats()`）が返すデータ全体。
interface AllTimeStats {
    totalCount: number;
    totalDays: number;
    monthlyCumulative: AllTimeMonthly[];
    careActionTotals: AllTimeActionTotal[];
    hasRecords: boolean;
}

const props = defineProps<{
    tab: Tab;
    baseDate: string | null;
    period: PeriodStats | null;
    allTime: AllTimeStats | null;
}>();

const { t, locale } = useTrans();
const { show } = useToast();
const { primaryButtonClass, focusRing } = useButtonClasses();
const { chipStyle, resolvedColor } = useCareActionSeriesColor();

// 非活性の「次」ボタンは`disabled`ではなく`aria-disabled`にする（History/Index.vueと同じ理由：
// `disabled`だとクリックイベントが発火せず、非活性の理由を伝えるトーストを出せなくなるため）。
function notifyAtLatestPeriod(): void {
    show(t('stats.next_period_locked_toast'), 'info');
}

defineOptions({
    layout: [AppLayout, { active: 'stats' }],
});

// タブの表示順と各タブの翻訳キー。
const tabs: { key: Tab; labelKey: string }[] = [
    { key: 'day', labelKey: 'stats.tab_day' },
    { key: 'week', labelKey: 'stats.tab_week' },
    { key: 'month', labelKey: 'stats.tab_month' },
    { key: 'all', labelKey: 'stats.tab_all' },
];

// タブ切替（日/週/月/全期間）では、粒度だけを変えて同じ時期を見続けられるよう基準日を引き継ぐ
// （docs/decisions.md §1.3「タブが選ぶのはバケットの粒度であり、1期間の選択ではない」という
// 読み方に沿う。全期間タブへは基準日の概念が無いため引き継がず、全期間タブから他タブへ戻る
// ときはbaseDateがnullなのでサーバー側の既定値（今日）にフォールバックする）。
function tabHref(tab: Tab): string {
    if (tab === 'all' || !props.baseDate) {
        return `/stats?tab=${tab}`;
    }

    return `/stats?tab=${tab}&base_date=${props.baseDate}`;
}

// 期間送り（‹／›ボタン）の遷移先URLを組み立てる。
function periodHref(baseDate: string): string {
    return `/stats?tab=${props.tab}&base_date=${baseDate}`;
}

// CSS変数（`--color-*`）の実際の値を取得する。Chart.jsはCanvas描画のためTailwindのクラス名では
// 色を指定できず、実際の色コード（`#RRGGBB`等）が必要になる。
function resolveCssVar(name: string): string {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

// `new Date('2026-07-15')`はUTC午前0時として解釈されローカルタイムゾーンによっては前日にずれるため、
// History/Index.vueと同じく年月日を分解してローカル時刻のDateを組み立てる。
function parseIsoDate(isoDate: string): Date {
    const [year, month, day] = isoDate.split('-').map(Number);

    return new Date(year, month - 1, day);
}

// 「2026/08/28 〜 09/03」のような範囲表記。翻訳キーを持たず、現ロケールを`Intl.DateTimeFormat`へ
// 渡して組み立てる（lang/*に日付書式を持たない方針はHistory/Index.vueと同じ）。
const rangeStartFormatter = computed(
    () => new Intl.DateTimeFormat(locale.value, { year: 'numeric', month: '2-digit', day: '2-digit' }),
);
const rangeEndFormatter = computed(() => new Intl.DateTimeFormat(locale.value, { month: '2-digit', day: '2-digit' }));

// 期間送りの間に表示する範囲表記（例：「2026/08/28 〜 09/03」）を組み立てる。
const rangeLabel = computed((): string => {
    if (!props.period || props.period.buckets.length === 0) {
        return '';
    }

    const buckets = props.period.buckets;
    const start = rangeStartFormatter.value.format(parseIsoDate(buckets[0].start));
    const end = rangeEndFormatter.value.format(parseIsoDate(buckets[buckets.length - 1].end));

    return `${start} 〜 ${end}`;
});

// バケットの列見出し。月タブだけ年をまたぎうるため年も出す。
const bucketLabelFormatter = computed(
    () =>
        new Intl.DateTimeFormat(
            locale.value,
            props.tab === 'month' ? { year: 'numeric', month: 'numeric' } : { month: 'numeric', day: 'numeric' },
        ),
);

// 1つのバケットの見出し（棒グラフのX軸ラベル・内訳表の列見出し）を組み立てる。
function bucketLabel(bucket: PeriodBucket): string {
    return bucketLabelFormatter.value.format(parseIsoDate(bucket.start));
}

// 全期間タブの折れ線グラフ用の月ラベル書式（例：「2025年10月」）。
const monthLabelFormatter = computed(() => new Intl.DateTimeFormat(locale.value, { year: 'numeric', month: 'short' }));

// 「YYYY-MM」形式の月キーを表示用ラベルに変換する。
function monthLabel(yearMonth: string): string {
    const [year, month] = yearMonth.split('-').map(Number);

    return monthLabelFormatter.value.format(new Date(year, month - 1, 1));
}

// 棒の上にバケット合計回数を置く（docs/wireframes.md S12）。Chart.jsコアには数値表示プラグインが
// 無いため、`chartjs-plugin-datalabels`のような追加npm依存を足さず、`plugins`propに渡す
// 1回限りの描画プラグインとして書く（docs/decisions.md §1.3「Chart.jsコアの標準機能のみ」）。
const bucketTotalPlugin = computed<Plugin<'bar'>>(() => ({
    id: 'bucketTotalLabel',
    afterDatasetsDraw(chart) {
        if (!props.period) {
            return;
        }

        // 先頭のデータセット（1つめの育児行動）が描画された各棒（バケットごと）の位置情報を取得する。
        // 積み上げ棒はX座標がバケット間で共通なので、ラベルのX位置を決めるだけならどのデータセットでもよい。
        const meta = chart.getDatasetMeta(0);
        const textColor = resolveCssVar('--color-text-primary');
        const { ctx } = chart;

        ctx.save();
        ctx.fillStyle = textColor;
        ctx.font = `600 12px ${CHART_FONT_FAMILY}`;
        ctx.textAlign = 'center';

        // barはバケット1つぶんの棒（インデックスがそのままバケット番号に対応）。
        meta.data.forEach((bar, index) => {
            const total = props.period?.buckets[index]?.total ?? 0;

            if (total === 0) {
                return;
            }

            // Y軸の値（件数）をCanvas上のピクセル座標に変換する（Canvasはデータ値ではなくピクセルで描画するため）。
            const y = chart.scales.y.getPixelForValue(total);
            // bar.xは棒の中心のX座標（ピクセル）。積み上げの一番上（yの6px上）に合計値を描く。
            ctx.fillText(String(total), bar.x, y - 6);
        });

        ctx.restore();
    },
}));

// 日/週/月タブの積み上げ棒グラフ用データ（X軸ラベル＋育児行動ごとのデータセット）を組み立てる。
const barChartData = computed(() => {
    if (!props.period) {
        return { labels: [], datasets: [] };
    }

    return {
        // X軸に並ぶ目盛ラベル（バケットの数だけ、例：「8/28」「8/29」…）。
        labels: props.period.buckets.map((bucket) => bucketLabel(bucket)),
        // 1育児行動＝1データセット＝積み上げ棒の中の1色。`data`は各バケットでの記録件数の配列で、
        // 積み上げ棒の縦方向の1単位（1目盛）＝記録1件を積んだ高さになる。
        datasets: props.period.series.map((series) => ({
            label: series.name,
            data: series.counts,
            backgroundColor: resolvedColor(series.careActionId),
            borderWidth: 0,
        })),
    };
});

// 積み上げ棒グラフの表示設定（軸・余白・凡例の有無）。
const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    // バケット合計ラベル（bucketTotalPlugin）を棒の6px上に描くため、最大値の棒がY軸の
    // 目盛最大値と一致する場合でもラベルがキャンバス上端で見切れないよう上に余白を確保する。
    layout: { padding: { top: 20 } },
    scales: {
        // X軸（横軸＝バケット）。`stacked: true`でデータセット（育児行動）ごとの棒を横に並べず縦に積む。
        x: { stacked: true, grid: { display: false } },
        // Y軸（縦軸＝件数）。`stacked: true`で同じX軸位置のdata値を合計した高さまで積み上げる。
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
    },
    plugins: {
        legend: { display: false },
    },
};

// 全期間タブの累計折れ線グラフ用データ（X軸ラベル＋月別累計のデータセット）を組み立てる。
const lineChartData = computed(() => {
    if (!props.allTime) {
        return { labels: [], datasets: [] };
    }

    const primary = resolveCssVar('--color-primary');

    return {
        // X軸に並ぶ目盛ラベル（月ごと、例：「2025年10月」）。
        labels: props.allTime.monthlyCumulative.map((entry) => monthLabel(entry.label)),
        // 折れ線は1系列のみ（積み上げ棒と違い育児行動別には分けない）。`data`は各月時点での累計件数で、
        // 各点のY座標＝その月末までの累計記録数になる。
        datasets: [
            {
                label: t('stats.all_time_total_count'),
                data: props.allTime.monthlyCumulative.map((entry) => entry.cumulativeTotal),
                borderColor: primary,
                backgroundColor: primary,
                tension: 0.3,
                pointRadius: 3,
            },
        ],
    };
});

// 累計折れ線グラフの表示設定（Y軸・凡例の有無）。
const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        // Y軸（縦軸＝累計件数）。積み上げ棒と違い`stacked`は使わない（データセットが1本のみのため）。
        y: { beginAtZero: true, ticks: { precision: 0 } },
    },
    plugins: {
        legend: { display: false },
    },
};
</script>

<template>
    <div>
        <h1 class="mb-6 text-heading-l font-bold">{{ t('stats.title') }}</h1>

        <!-- タブ（日/週/月/全期間）。AppLayoutの選択中ナビと同じ配色（bg-primary-subtle text-primary）。 -->
        <div role="tablist" class="flex gap-1 rounded-xl border border-border bg-surface p-1">
            <Link
                v-for="item in tabs"
                :key="item.key"
                :href="tabHref(item.key)"
                role="tab"
                :aria-selected="item.key === tab"
                :class="[
                    'flex min-h-11 flex-1 items-center justify-center rounded-lg px-3 text-center text-label font-semibold',
                    focusRing,
                    item.key === tab ? 'bg-primary-subtle text-primary' : 'text-text-secondary hover:text-primary',
                ]"
            >
                {{ t(item.labelKey) }}
            </Link>
        </div>

        <!-- 期間送り（全期間タブでは表示しない。docs/wireframes.md S12） -->
        <div v-if="tab !== 'all' && period" class="mt-4 flex items-center justify-center gap-4">
            <Link
                :href="periodHref(period.prevBaseDate)"
                :aria-label="t('stats.prev_period')"
                :class="[
                    'flex min-h-11 min-w-11 items-center justify-center rounded-full text-heading-m text-text-secondary hover:text-primary',
                    focusRing,
                ]"
            >
                ‹
            </Link>
            <span class="text-body font-semibold text-text-primary">{{ rangeLabel }}</span>
            <Link
                v-if="!period.atLatestPeriod"
                :href="periodHref(period.nextBaseDate)"
                :aria-label="t('stats.next_period')"
                :class="[
                    'flex min-h-11 min-w-11 items-center justify-center rounded-full text-heading-m text-text-secondary hover:text-primary',
                    focusRing,
                ]"
            >
                ›
            </Link>
            <!-- 育児ログは未来日時に存在しえないため、最新期間より先へは進めない
                 （`disabled`にせず`aria-disabled`にする理由はnotifyAtLatestPeriod()参照）。 -->
            <button
                v-else
                type="button"
                aria-disabled="true"
                :aria-label="t('stats.next_period')"
                :class="[
                    'flex min-h-11 min-w-11 cursor-not-allowed items-center justify-center rounded-full text-heading-m text-text-secondary',
                    focusRing,
                ]"
                @click="notifyAtLatestPeriod"
            >
                ›
            </button>
        </div>

        <!-- 日/週/月タブ -->
        <template v-if="tab !== 'all' && period">
            <div v-if="!period.hasRecords" class="flex flex-col items-center gap-2 py-16 text-center">
                <p class="text-heading-m font-semibold text-text-primary">{{ t('stats.empty_title') }}</p>
                <p class="text-body text-text-secondary">{{ t('stats.empty_body') }}</p>
                <Link href="/" :class="['mt-4 inline-flex min-h-11 items-center justify-center', primaryButtonClass]">
                    {{ t('stats.empty_cta') }}
                </Link>
            </div>

            <template v-else>
                <div class="mt-6 h-64 md:h-80">
                    <Bar :data="barChartData" :options="barChartOptions" :plugins="[bucketTotalPlugin]" />
                </div>

                <!-- 内訳マトリクス（育児行動 × 7バケット）。色チップがグラフの凡例を兼ねる（DESIGN.md 5.5節）。
                     Chart.jsはCanvas描画でスクリーンリーダーが内容を読めないため、この表が代替テキストも兼ねる。
                     見た目はCSS Gridのままだが、`display: grid`は要素の暗黙のテーブルロールを潰すため、
                     支援技術に表構造が伝わるよう`role`を明示する（横スクロールはさせない：グラフの7本と
                     表の7列が常に視覚的に対応している必要があるため。docs/wireframes.md S12）。 -->
                <div class="mt-6" role="table" :aria-label="t('stats.breakdown_table_label')">
                    <div
                        role="row"
                        class="grid grid-cols-7 gap-x-1 border-b border-border pb-2 text-body-sm font-semibold text-text-secondary md:grid-cols-[minmax(96px,1fr)_repeat(7,minmax(0,1fr))]"
                    >
                        <div role="columnheader" class="col-span-7 md:col-span-1">
                            {{ t('stats.breakdown_action_header') }}
                        </div>
                        <div v-for="bucket in period.buckets" :key="bucket.start" role="columnheader" class="text-center">
                            {{ bucketLabel(bucket) }}
                        </div>
                    </div>
                    <div
                        v-for="row in period.series"
                        :key="row.careActionId"
                        role="row"
                        class="grid grid-cols-7 items-center gap-x-1 gap-y-1 border-b border-border py-2 last:border-b-0 md:grid-cols-[minmax(96px,1fr)_repeat(7,minmax(0,1fr))]"
                    >
                        <div role="rowheader" class="col-span-7 flex items-center gap-2 md:col-span-1">
                            <span class="h-4 w-1 shrink-0 rounded-full" :style="chipStyle(row.careActionId)"></span>
                            <span class="text-body-sm font-semibold text-text-primary">{{ row.name }}</span>
                        </div>
                        <div
                            v-for="(count, index) in row.counts"
                            :key="index"
                            role="cell"
                            class="text-center text-body-sm text-text-secondary"
                        >
                            {{ count }}
                        </div>
                    </div>
                </div>
            </template>
        </template>

        <!-- 全期間タブ -->
        <template v-else-if="tab === 'all' && allTime">
            <div v-if="!allTime.hasRecords" class="mt-4 flex flex-col items-center gap-2 py-16 text-center">
                <p class="text-heading-m font-semibold text-text-primary">{{ t('stats.empty_title') }}</p>
                <p class="text-body text-text-secondary">{{ t('stats.empty_body') }}</p>
                <Link href="/" :class="['mt-4 inline-flex min-h-11 items-center justify-center', primaryButtonClass]">
                    {{ t('stats.empty_cta') }}
                </Link>
            </div>

            <template v-else>
                <div class="mt-4 rounded-2xl border border-border bg-surface p-4">
                    <div class="flex items-center justify-between py-1">
                        <span class="text-body text-text-secondary">{{ t('stats.all_time_total_count') }}</span>
                        <span class="text-heading-m font-bold text-text-primary">
                            {{ t('stats.count_unit', { count: allTime.totalCount }) }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between py-1">
                        <span class="text-body text-text-secondary">{{ t('stats.all_time_total_days') }}</span>
                        <span class="text-heading-m font-bold text-text-primary">
                            {{ t('stats.days_unit', { days: allTime.totalDays }) }}
                        </span>
                    </div>
                </div>

                <div class="mt-6 h-64 md:h-80">
                    <Line :data="lineChartData" :options="lineChartOptions" />
                </div>

                <!-- 育児行動ごとの累計（多い順）。「行動の種類ごとの順位」は許容されるランキング（CLAUDE.md）。 -->
                <div class="mt-6">
                    <div
                        v-for="row in allTime.careActionTotals"
                        :key="row.careActionId"
                        class="flex items-center justify-between gap-2 border-b border-border py-2 last:border-b-0"
                    >
                        <div class="flex items-center gap-2">
                            <span class="h-4 w-1 shrink-0 rounded-full" :style="chipStyle(row.careActionId)"></span>
                            <span class="text-body-sm font-semibold text-text-primary">{{ row.name }}</span>
                        </div>
                        <span class="text-body-sm text-text-secondary">{{ t('stats.count_unit', { count: row.total }) }}</span>
                    </div>
                </div>
            </template>
        </template>
    </div>
</template>
