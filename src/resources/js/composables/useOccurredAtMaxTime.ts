import { computed, onMounted, onUnmounted, ref, type ComputedRef, type Ref } from 'vue';

/**
 * 実施時刻入力欄（`<input type="time">`）の選択上限を、選択中の実施日に応じて返す composable。
 *
 * S10（新規記録）とS11（ログ編集）は入力欄そのものを `Components/CareLogFormFields.vue` で
 * 共用しており、サーバー側のバリデーションも `StoreCareLogRequest`／`UpdateCareLogRequest` で
 * 共通のため、UI側の上限計算もこの1箇所に集約する（別々に書くとUIとサーバーの許容範囲が
 * 食い違う）。UIの制限はサーバー側バリデーションの代替ではなく二重担保（docs/wireframes.md S10）。
 */

/** 端末クロックの軽微なズレを吸収するバッファ（サーバー側の許容範囲と同じ5分）。 */
const FUTURE_BUFFER_MINUTES = 5;

/** 上限の基準となる現在時刻を測り直す間隔。 */
const NOW_REFRESH_INTERVAL_MS = 30_000;

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

/**
 * 現在時刻を`<input type="time">`のvalue形式（'HH:MM'）で返す。
 *
 * S10で実施時刻の初期値に使う。上限（`maxTime`）と同じ書式・同じ丸め方で組み立てる必要が
 * あるため、composable本体と同じ`pad()`を共有する。
 */
export function currentTimeString(): string {
    const now = new Date();

    return `${pad(now.getHours())}:${pad(now.getMinutes())}`;
}

export function useOccurredAtMaxTime(
    occurredDate: Ref<string>,
    todayDate: string,
): { maxTime: ComputedRef<string | undefined> } {
    // 上限が「画面を開いた瞬間の現在時刻」に固定されないよう、基準時刻をrefにして一定間隔で更新する。
    // 画面を開いたまま数分放置しても、上限が古いままにならない。
    const now = ref(new Date());
    let nowTimer: ReturnType<typeof setInterval> | null = null;

    onMounted(() => {
        nowTimer = setInterval(() => {
            now.value = new Date();
        }, NOW_REFRESH_INTERVAL_MS);
    });

    onUnmounted(() => {
        if (nowTimer) {
            clearInterval(nowTimer);
        }
    });

    // 実施日が「今日」の場合のみ、実施時刻の選択上限を「現在時刻＋5分」に制限する。
    // 過去日を選んだ場合は00:00〜23:59を自由に選べる（docs/wireframes.md S10）。
    const maxTime = computed<string | undefined>(() => {
        if (occurredDate.value !== todayDate) {
            return undefined;
        }

        const limit = new Date(now.value.getTime() + FUTURE_BUFFER_MINUTES * 60 * 1000);
        const limitDate = `${limit.getFullYear()}-${pad(limit.getMonth() + 1)}-${pad(limit.getDate())}`;

        // 現在時刻が23:55以降だと「現在時刻+5分」が翌日に繰り上がる。<input type="time">は
        // `min`を指定しない限り`max`のラップアラウンドを解釈しないため、繰り上がった値
        // （実質00:00〜00:04）をそのまま渡すと23:59までの時刻がすべて無効になってしまう。
        // 日付が繰り上がる場合は上限を23:59にフォールバックする。
        if (limitDate !== todayDate) {
            return '23:59';
        }

        return `${pad(limit.getHours())}:${pad(limit.getMinutes())}`;
    });

    return { maxTime };
}
