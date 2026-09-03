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
import { useTrans } from '@/composables/useTrans';

// Chart.jsはコアの標準機能のみで描く（docs/decisions.md §1.3）。`registerables`（全要素）は登録せず、
// 日/週/月タブの積み上げ棒と全期間タブの折れ線が使う要素だけ登録する。凡例（Legend）は置かない
// 仕様（DESIGN.md 5.5節）のため登録しない。
Chart.register(CategoryScale, LinearScale, BarElement, LineElement, PointElement, Tooltip);

// Canvas内だけ別フォントになるのを防ぐ（DESIGN.md 6.1節・implementation-plan.md M7備考）。
Chart.defaults.font.family =
    "'Noto Sans JP', -apple-system, BlinkMacSystemFont, 'Hiragino Kaku Gothic ProN', sans-serif";

type Tab = 'day' | 'week' | 'month' | 'all';

interface PeriodBucket {
    start: string;
    end: string;
    total: number;
}

interface PeriodSeries {
    careActionId: number;
    name: string;
    counts: number[];
}

interface PeriodStats {
    buckets: PeriodBucket[];
    series: PeriodSeries[];
    hasRecords: boolean;
    prevBaseDate: string;
    nextBaseDate: string;
}

interface AllTimeMonthly {
    label: string;
    cumulativeTotal: number;
}

interface AllTimeActionTotal {
    careActionId: number;
    name: string;
    total: number;
}

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
const { primaryButtonClass } = useButtonClasses();
const { chipStyle, resolvedColor } = useCareActionSeriesColor();

defineOptions({
    layout: [AppLayout, { active: 'stats' }],
});

const tabs: { key: Tab; labelKey: string }[] = [
    { key: 'day', labelKey: 'stats.tab_day' },
    { key: 'week', labelKey: 'stats.tab_week' },
    { key: 'month', labelKey: 'stats.tab_month' },
    { key: 'all', labelKey: 'stats.tab_all' },
];

function tabHref(tab: Tab): string {
    return `/stats?tab=${tab}`;
}

function periodHref(baseDate: string): string {
    return `/stats?tab=${props.tab}&base_date=${baseDate}`;
}

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

function bucketLabel(bucket: PeriodBucket): string {
    return bucketLabelFormatter.value.format(parseIsoDate(bucket.start));
}

const monthLabelFormatter = computed(() => new Intl.DateTimeFormat(locale.value, { year: 'numeric', month: 'short' }));

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

        const meta = chart.getDatasetMeta(0);
        const textColor = resolveCssVar('--color-text-primary');
        const { ctx } = chart;

        ctx.save();
        ctx.fillStyle = textColor;
        ctx.font = "600 12px 'Noto Sans JP'";
        ctx.textAlign = 'center';

        meta.data.forEach((bar, index) => {
            const total = props.period?.buckets[index]?.total ?? 0;

            if (total === 0) {
                return;
            }

            const y = chart.scales.y.getPixelForValue(total);
            ctx.fillText(String(total), bar.x, y - 6);
        });

        ctx.restore();
    },
}));

const barChartData = computed(() => {
    if (!props.period) {
        return { labels: [], datasets: [] };
    }

    return {
        labels: props.period.buckets.map((bucket) => bucketLabel(bucket)),
        datasets: props.period.series.map((series) => ({
            label: series.name,
            data: series.counts,
            backgroundColor: resolvedColor(series.careActionId),
            borderWidth: 0,
        })),
    };
});

const barChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        x: { stacked: true, grid: { display: false } },
        y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } },
    },
    plugins: {
        legend: { display: false },
    },
};

const lineChartData = computed(() => {
    if (!props.allTime) {
        return { labels: [], datasets: [] };
    }

    const primary = resolveCssVar('--color-primary');

    return {
        labels: props.allTime.monthlyCumulative.map((entry) => monthLabel(entry.label)),
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

const lineChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
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
                class="flex min-h-11 min-w-11 items-center justify-center rounded-full text-heading-m text-text-secondary hover:text-primary"
            >
                ‹
            </Link>
            <span class="text-body font-semibold text-text-primary">{{ rangeLabel }}</span>
            <Link
                :href="periodHref(period.nextBaseDate)"
                :aria-label="t('stats.next_period')"
                class="flex min-h-11 min-w-11 items-center justify-center rounded-full text-heading-m text-text-secondary hover:text-primary"
            >
                ›
            </Link>
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
                     Chart.jsはCanvas描画でスクリーンリーダーが内容を読めないため、この表が代替テキストも兼ねる。 -->
                <div class="mt-6 overflow-x-auto">
                    <div
                        class="grid grid-cols-7 gap-x-1 border-b border-border pb-2 text-body-sm font-semibold text-text-secondary md:grid-cols-[minmax(96px,1fr)_repeat(7,minmax(0,1fr))]"
                    >
                        <div class="col-span-7 md:col-span-1">{{ t('stats.breakdown_action_header') }}</div>
                        <div v-for="bucket in period.buckets" :key="bucket.start" class="text-center">
                            {{ bucketLabel(bucket) }}
                        </div>
                    </div>
                    <div
                        v-for="row in period.series"
                        :key="row.careActionId"
                        class="grid grid-cols-7 items-center gap-x-1 gap-y-1 border-b border-border py-2 last:border-b-0 md:grid-cols-[minmax(96px,1fr)_repeat(7,minmax(0,1fr))]"
                    >
                        <div class="col-span-7 flex items-center gap-2 md:col-span-1">
                            <span class="h-1 w-4 shrink-0 rounded-full" :style="chipStyle(row.careActionId)"></span>
                            <span class="text-body-sm font-semibold text-text-primary">{{ row.name }}</span>
                        </div>
                        <div
                            v-for="(count, index) in row.counts"
                            :key="index"
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
                            <span class="h-1 w-4 shrink-0 rounded-full" :style="chipStyle(row.careActionId)"></span>
                            <span class="text-body-sm font-semibold text-text-primary">{{ row.name }}</span>
                        </div>
                        <span class="text-body-sm text-text-secondary">{{ t('stats.count_unit', { count: row.total }) }}</span>
                    </div>
                </div>
            </template>
        </template>
    </div>
</template>
