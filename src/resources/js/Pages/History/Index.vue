<script setup lang="ts">
// Inertia::render('History/Index')（HistoryController@index）が読み込むS13のページ。
// 日付ごとにグループ化した新しい順のタイムラインで、各行の「…」からS11（ログ編集）へ遷移する
// （docs/wireframes.md S13）。
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useToast } from '@/composables/useToast';
import { useTrans } from '@/composables/useTrans';

interface HistoryLog {
    id: string;
    // 'HH:MM'。サーバー側で整形済み（docs/data-model.md ④の`occurred_at`は秒精度だが、
    // 一覧では分までで足りる）。
    time: string;
    // サーバー側が`careAction?->name`のnull安全演算子で取得しているため型としてはnullを許容する
    // （実運用ではFK制約により常に存在する。Record/Index.vueの`Slot`と同じ扱い）。
    careActionName: string | null;
    memo: string | null;
    // 「7日前の00:00」より後か。サーバー側の`CareLogPolicy`と同じ`CareLogWindow`由来の値で、
    // クライアント側で日付計算をやり直さない（1日ズレると「操作できるのに保存できない」行になる）。
    editable: boolean;
}

interface HistoryDay {
    // ISO形式（YYYY-MM-DD）。表示用の整形はロケール依存のためクライアント側で行う。
    date: string;
    logs: HistoryLog[];
}

const props = defineProps<{
    days: HistoryDay[];
    // `history.locked_toast`の`:days`に渡す遡り可能日数（`config('totoops.care_log.backdate_days')`）。
    backdateDays: number;
}>();

const { t, locale } = useTrans();
const { show } = useToast();
const { primaryButtonClass } = useButtonClasses();

defineOptions({
    layout: [AppLayout, { active: 'history' }],
});

// 「2026年7月15日」のような日付見出しは`lang/*`に文言として持たず、現ロケールから組み立てる
// （英語ロケール追加時に`lang/en`へ日付書式を書き足す必要がなくなる）。
const dateFormatter = computed(
    () => new Intl.DateTimeFormat(locale.value, { year: 'numeric', month: 'long', day: 'numeric' }),
);

function formatDate(isoDate: string): string {
    // `new Date('2026-07-15')`はUTC午前0時として解釈されるため、UTCより西のタイムゾーンの端末では
    // 前日として表示されてしまう。年月日を分解してローカル時刻のDateを組み立てる。
    const [year, month, day] = isoDate.split('-').map(Number);

    return dateFormatter.value.format(new Date(year, month - 1, day));
}

// 非活性の「…」は`disabled`属性を付けずに`aria-disabled`で表す。`disabled`にすると
// クリックイベント自体が発火せず、「なぜ操作できないか」を伝えるトースト（docs/wireframes.md S13）
// を出せなくなるため。無反応のまま放置すると利用者は不具合と受け取る。
function notifyLocked(): void {
    show(t('history.locked_toast', { days: props.backdateDays }), 'info');
}
</script>

<template>
    <div>
        <h1 class="mb-6 text-heading-l font-bold">{{ t('history.title') }}</h1>

        <!-- 空状態：やさしい文言＋S3へのPrimary CTA（DESIGN.md 11章 Empty、docs/wireframes.md S13空状態） -->
        <div v-if="days.length === 0" class="flex flex-col items-center gap-2 py-16 text-center">
            <p class="text-heading-m font-semibold text-text-primary">{{ t('history.empty_title') }}</p>
            <p class="text-body text-text-secondary">{{ t('history.empty_body') }}</p>
            <Link
                href="/"
                :class="['mt-4 inline-flex min-h-11 items-center justify-center', primaryButtonClass]"
            >
                {{ t('history.empty_cta') }}
            </Link>
        </div>

        <template v-else>
            <section v-for="day in days" :key="day.date" class="mb-6">
                <h2 class="mb-2 text-heading-m font-semibold text-text-primary">{{ formatDate(day.date) }}</h2>

                <!-- 行の区切りは1pxの下線のみ。ゼブラストライプは使わない（DESIGN.md 10章 Tables and Lists） -->
                <ul class="divide-y divide-border border-t border-border">
                    <li v-for="log in day.logs" :key="log.id" class="flex items-center gap-3 py-2">
                        <span class="text-body-sm tabular-nums text-text-secondary">{{ log.time }}</span>

                        <div class="min-w-0 flex-1">
                            <p class="text-body text-text-primary">{{ log.careActionName }}</p>
                            <!-- メモがある行だけ2行目に出す。長文は省略表示し、全文はS11で確認・編集する
                                 （docs/wireframes.md S13） -->
                            <p v-if="log.memo" class="truncate text-body-sm text-text-secondary">{{ log.memo }}</p>
                        </div>

                        <!-- 編集可能な行の右端は「…」ケバブメニュー（DESIGN.md 10章 Tables and Lists） -->
                        <Link
                            v-if="log.editable"
                            :href="`/care-logs/${log.id}/edit`"
                            :aria-label="t('history.edit_menu', { time: log.time, name: log.careActionName ?? '' })"
                            class="flex min-h-11 min-w-11 shrink-0 items-center justify-center rounded-md text-heading-m text-text-primary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                        >
                            <span aria-hidden="true">…</span>
                        </Link>
                        <button
                            v-else
                            type="button"
                            aria-disabled="true"
                            :aria-label="t('history.locked_menu', { time: log.time, name: log.careActionName ?? '' })"
                            class="flex min-h-11 min-w-11 shrink-0 cursor-not-allowed items-center justify-center rounded-md text-heading-m text-text-secondary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                            @click="notifyLocked"
                        >
                            <span aria-hidden="true">…</span>
                        </button>
                    </li>
                </ul>
            </section>
        </template>
    </div>
</template>
