<script setup lang="ts">
// Inertia::render('Record/Index')（RecordController@index）が読み込むS3のページコンポーネント。
// 短タップ＝タップ時刻を`occurred_at`として即記録（POST /care-logs）、
// 長押し＝実施日時指定画面（S10）へ遷移（docs/wireframes.md S3, docs/decisions.md §1.3）。
import { Link, router } from '@inertiajs/vue3';
import { onUnmounted, ref } from 'vue';
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

// 直近の記録失敗のエラーメッセージ。バリデーション・重複エラーは`router.post`がページ遷移をせず
// S3に留まるだけなので、明示的にインラインバナーへ出さないと利用者が失敗に気付けない
// （DESIGN.md 11章 Error「通信エラー等の全体エラー：トーストまたはインラインバナーで表示し、
// Primaryボタンでの再試行導線を必ず添える」）。
const errorMessage = ref<string | null>(null);
const lastFailedSlot = ref<Slot | null>(null);

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
    errorMessage.value = null;

    router.post(
        '/care-logs',
        {
            care_action_id: slot.careActionId,
            occurred_at: formatOccurredAt(new Date()),
        },
        {
            onError: (errors) => {
                lastFailedSlot.value = slot;
                errorMessage.value = Object.values(errors)[0] ?? t('record.record_failed_generic');
            },
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}

function retryLastFailedRecord(): void {
    if (lastFailedSlot.value) {
        recordNow(lastFailedSlot.value);
    }
}

function goToDateTimePicker(slot: Slot): void {
    router.visit(`/care-logs/create?care_action_id=${slot.careActionId}`);
}

function startPress(event: PointerEvent, slot: Slot): void {
    // マウスの右クリック・中クリック（`button !== 0`）は無視する。無視しないと、
    // ロングプレス用タイマーが副ボタンでも起動してしまう（endPress側の同ガードと対）。
    if (event.button !== 0) {
        return;
    }

    // 前のタイマーを確実にクリアしてから新しいタイマーを張る。`pressTimer`はタイル間で共有される
    // モジュール変数1組しかないため、これを怠るとマルチタッチ（タイルAに触れたまま指を増やして
    // タイルBに触れる）でAのタイマー参照が失われ、Aを離してcancelPress()が走ってもクリアされない。
    // 500ms後にAのタイマーが発火し、触れていないタイルAのS10へ勝手に遷移してしまう。
    cancelPress();

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

// 押している最中にグローバルナビ等で別画面へ遷移した場合も、タイマーが生き残っていると
// 遷移直後にS10へ飛ばしてしまうため、アンマウント時に確実に解除する。
onUnmounted(cancelPress);

function endPress(event: PointerEvent, slot: Slot): void {
    // 右クリック等の副ボタンでの`pointerup`では即記録を発火しない
    // （`@contextmenu.prevent`によりコンテキストメニューが出ないため、無視しないと無音でINSERTされる）。
    if (event.button !== 0) {
        cancelPress();
        return;
    }

    const wasLongPress = longPressTriggered;
    cancelPress();

    if (wasLongPress) {
        return;
    }

    recordNow(slot);
}

function handleKeyboardActivation(event: MouseEvent, slot: Slot): void {
    // <button>をキーボード（Enter/Space）で起動した場合、ブラウザは合成の`click`イベントを
    // 発火させるが`pointerdown`/`pointerup`は発火しない。一方マウス・タッチ操作は既に
    // `endPress`（pointerup）側で処理済みで、その後にも`click`が発火するため、ここで無条件に
    // `recordNow`を呼ぶと二重送信になる。キーボード・支援技術由来のclickは`detail === 0`
    // （クリック回数を持たない）になることを利用して区別する。
    if (event.detail !== 0) {
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

        <div
            v-if="errorMessage"
            role="alert"
            class="mb-4 flex items-center justify-between gap-3 rounded-md border border-error bg-surface px-4 py-3 text-body-sm text-error"
        >
            <span class="flex items-center gap-2">
                <span aria-hidden="true">⚠️</span>
                <span>{{ errorMessage }}</span>
            </span>
            <button
                type="button"
                class="shrink-0 rounded-xl bg-primary px-3 py-2 text-label font-semibold text-white hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                @click="retryLastFailedRecord"
            >
                {{ t('record.retry') }}
            </button>
        </div>

        <!-- グリッドは4列×2段固定（docs/wireframes.md S3） -->
        <div class="grid grid-cols-4 gap-2">
            <template v-for="(slot, index) in slots" :key="index">
                <button
                    v-if="slot"
                    type="button"
                    :disabled="submitting"
                    :aria-label="`${slot.name}（${t('record.long_press_hint')}）`"
                    class="group flex aspect-square flex-col items-center justify-center gap-1 rounded-[20px] border border-border bg-surface px-2 text-center focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:bg-border"
                    @pointerdown="startPress($event, slot)"
                    @pointerup="endPress($event, slot)"
                    @pointerleave="cancelPress"
                    @pointercancel="cancelPress"
                    @click="handleKeyboardActivation($event, slot)"
                    @contextmenu.prevent
                >
                    <!-- line-clamp-3：長い育児行動名（例「送迎（保育園・習い事等）」）でも正方形タイルからはみ出さないよう3行で切る -->
                    <span class="line-clamp-3 text-label font-semibold text-text-primary group-disabled:text-text-secondary">{{
                        slot.name
                    }}</span>
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
