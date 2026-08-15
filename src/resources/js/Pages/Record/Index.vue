<script setup lang="ts">
// Inertia::render('Record/Index')（RecordController@index）が読み込むS3のページコンポーネント。
// 短タップ＝タップ時刻を`occurred_at`として即記録（POST /care-logs）、
// 長押し＝実施日時指定画面（S10）へ遷移（docs/wireframes.md S3, docs/decisions.md §1.3）。
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';

interface Slot {
    careActionId: number;
    // サーバー側（RecordController@index）は `careAction?->name` のnull安全演算子で取得しているため、
    // 型としてはnullを許容する（実運用ではFK制約により常に存在するが、型は実装に合わせて揃える）。
    name: string | null;
}

defineProps<{
    // slot_position（1〜8）順の配列。行が無い位置はnull（空きスロット）。
    slots: (Slot | null)[];
}>();

const { t } = useTrans();

defineOptions({
    layout: [AppLayout, { active: 'record' }],
});

// 送信中は全タイルをdisableし、連打による二重送信を防ぐ
// （サーバー側の`UNIQUE(user_id, care_action_id, occurred_at)`はあくまで最後の砦。docs/decisions.md §1.3）。
const submitting = ref(false);

const LONG_PRESS_MS = 500;
let pressTimer: ReturnType<typeof setTimeout> | null = null;
let longPressTriggered = false;

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

// `occurred_at`は秒精度（docs/data-model.md ④）。タップ時点のタイムスタンプをそのまま送る
// （サーバー採番の`now()`だとリクエストごとに値が変わり、二重送信防止のUNIQUE制約が効かないため）。
function formatOccurredAt(date: Date): string {
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

function recordNow(slot: Slot): void {
    if (submitting.value) {
        return;
    }
    submitting.value = true;

    router.post(
        '/care-logs',
        {
            care_action_id: slot.careActionId,
            occurred_at: formatOccurredAt(new Date()),
        },
        {
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}

function goToDateTimePicker(slot: Slot): void {
    router.visit(`/care-logs/create?care_action_id=${slot.careActionId}`);
}

function startPress(slot: Slot): void {
    longPressTriggered = false;
    pressTimer = setTimeout(() => {
        longPressTriggered = true;
        goToDateTimePicker(slot);
    }, LONG_PRESS_MS);
}

function cancelPress(): void {
    if (pressTimer) {
        clearTimeout(pressTimer);
        pressTimer = null;
    }
}

function endPress(slot: Slot): void {
    const wasLongPress = longPressTriggered;
    cancelPress();

    if (wasLongPress) {
        return;
    }

    recordNow(slot);
}
</script>

<template>
    <div>
        <header class="mb-6 flex items-center justify-between">
            <!-- Google SSOのユーザーアイコンは表示のみ・DB非保存の想定（docs/screens.md S3）。
                 実際のアイコン取得・表示はこのマイルストーンの対象外のため、仮の絵文字を置く -->
            <span class="text-heading-l" aria-hidden="true">👤</span>
            <h1 class="text-heading-l font-bold">{{ t('record.title') }}</h1>
        </header>

        <!-- グリッドは4列×2段固定（docs/wireframes.md S3） -->
        <div class="grid grid-cols-4 gap-2">
            <template v-for="(slot, index) in slots" :key="index">
                <button
                    v-if="slot"
                    type="button"
                    :disabled="submitting"
                    :aria-label="`${slot.name}（${t('record.long_press_hint')}）`"
                    class="flex aspect-square flex-col items-center justify-center gap-1 rounded-[20px] border border-border bg-surface px-2 text-center focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-60"
                    @pointerdown="startPress(slot)"
                    @pointerup="endPress(slot)"
                    @pointerleave="cancelPress"
                    @pointercancel="cancelPress"
                    @contextmenu.prevent
                >
                    <!-- line-clamp-3：長い育児行動名（例「送迎（保育園・習い事等）」）でも正方形タイルからはみ出さないよう3行で切る -->
                    <span class="line-clamp-3 text-label font-semibold text-text-primary">{{ slot.name }}</span>
                </button>
                <div
                    v-else
                    class="flex aspect-square flex-col items-center justify-center gap-1 rounded-[20px] border border-border bg-surface px-2 text-center"
                >
                    <span class="text-heading-m font-semibold text-text-secondary" aria-hidden="true">＋</span>
                    <span class="text-body-sm text-text-secondary">{{ t('record.empty_slot') }}</span>
                </div>
            </template>
        </div>

        <div class="mt-6 flex justify-center">
            <Link
                href="/care-actions/other"
                class="rounded-xl border border-border bg-transparent px-5 py-3 text-label font-semibold text-secondary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
            >
                {{ t('record.other') }}
            </Link>
        </div>
    </div>
</template>
