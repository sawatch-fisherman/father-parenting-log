<script setup lang="ts">
// Inertia::render('CareLogs/Create')（CareLogController@create）が読み込むS10のページ。
// S3の長押し、またはS4の項目タップから遷移する（docs/wireframes.md S10）。
// 単機能画面のためグローバルナビは表示しない（AppLayout未使用）。
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { useTrans } from '@/composables/useTrans';

interface CareAction {
    id: number;
    name: string;
}

const props = defineProps<{
    careAction: CareAction;
    // 「7日前〜今日」（docs/decisions.md §1.3）。YYYY-MM-DD形式。
    backdateFloorDate: string;
    todayDate: string;
}>();

const { t } = useTrans();

function pad(value: number): string {
    return String(value).padStart(2, '0');
}

const now = new Date();

// 実施日・実施時刻は個別の入力欄（<input type="date">/<input type="time">）を持つが、
// サーバーへは結合済みの `occurred_at` として送る。`form.errors.occurred_at` を
// そのまま使えるよう、`occurred_at` はここではローカルrefにせずuseFormのフィールドにする。
const occurredDate = ref(props.todayDate);
const occurredTime = ref(`${pad(now.getHours())}:${pad(now.getMinutes())}`);

const form = useForm({
    care_action_id: props.careAction.id,
    occurred_at: '',
    // 空欄（メモなし）は `StoreCareLogRequest::prepareForValidation()` がサーバー側でnullに正規化する。
    memo: '',
});

// 実施日が「今日」の場合のみ、実施時刻の選択上限を「現在時刻＋5分」に制限する。
// 過去日を選んだ場合は00:00〜23:59を自由に選べる（docs/wireframes.md S10）。
// サーバー側でも同じ条件（`StoreCareLogRequest`）で弾くため、これは二重担保。
const maxTime = computed<string | undefined>(() => {
    if (occurredDate.value !== props.todayDate) {
        return undefined;
    }

    const limit = new Date(Date.now() + 5 * 60 * 1000);
    return `${pad(limit.getHours())}:${pad(limit.getMinutes())}`;
});

function submit(): void {
    form.occurred_at = `${occurredDate.value} ${occurredTime.value}:00`;
    form.post('/care-logs');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-text-primary">
        <div class="px-4 pt-6">
            <Link
                href="/"
                class="inline-flex min-h-11 items-center text-body-sm text-secondary hover:text-text-primary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
            >
                {{ t('care_logs.back') }}
            </Link>
        </div>

        <div class="flex flex-1 flex-col items-center px-4">
            <form class="w-full max-w-sm space-y-6" @submit.prevent="submit">
                <!-- ヘッダーには選択済みの育児行動名を表示のみ（docs/wireframes.md S10） -->
                <h1 class="text-center text-heading-l font-bold">{{ careAction.name }}</h1>

                <div class="space-y-1">
                    <label for="occurred_date" class="block text-label font-semibold text-text-primary">{{
                        t('care_logs.date_label')
                    }}</label>
                    <input
                        id="occurred_date"
                        v-model="occurredDate"
                        type="date"
                        :min="backdateFloorDate"
                        :max="todayDate"
                        class="w-full rounded-md border bg-surface px-4 py-3 text-body text-text-primary focus:border-primary focus:outline-none focus:ring-[3px] focus:ring-primary/25"
                        :class="form.errors.occurred_at ? 'border-error' : 'border-border'"
                    />
                    <p class="text-body-sm text-text-secondary">{{ t('care_logs.date_help') }}</p>
                </div>

                <div class="space-y-1">
                    <label for="occurred_time" class="block text-label font-semibold text-text-primary">{{
                        t('care_logs.time_label')
                    }}</label>
                    <input
                        id="occurred_time"
                        v-model="occurredTime"
                        type="time"
                        :max="maxTime"
                        class="w-full rounded-md border bg-surface px-4 py-3 text-body text-text-primary focus:border-primary focus:outline-none focus:ring-[3px] focus:ring-primary/25"
                        :class="form.errors.occurred_at ? 'border-error' : 'border-border'"
                    />
                </div>

                <p v-if="form.errors.occurred_at" class="text-body-sm text-error">{{ form.errors.occurred_at }}</p>

                <div class="space-y-1">
                    <label for="memo" class="block text-label font-semibold text-text-primary">{{ t('care_logs.memo_label') }}</label>
                    <textarea
                        id="memo"
                        v-model="form.memo"
                        maxlength="255"
                        rows="3"
                        class="w-full rounded-md border bg-surface px-4 py-3 text-body text-text-primary focus:border-primary focus:outline-none focus:ring-[3px] focus:ring-primary/25"
                        :class="form.errors.memo ? 'border-error' : 'border-border'"
                    ></textarea>
                    <p v-if="form.errors.memo" class="text-body-sm text-error">{{ form.errors.memo }}</p>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-primary px-5 py-3 text-label font-semibold text-white hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:bg-border disabled:text-text-secondary"
                    :disabled="form.processing"
                >
                    {{ t('care_logs.submit') }}
                </button>
            </form>
        </div>
    </div>
</template>
