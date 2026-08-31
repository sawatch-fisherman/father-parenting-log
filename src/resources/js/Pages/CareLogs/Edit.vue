<script setup lang="ts">
// Inertia::render('CareLogs/Edit')（CareLogController@edit）が読み込むS11のページ。
// S13の各行「…」から遷移し、`occurred_at` と `memo` だけを変更できる。育児行動を変えたい場合は
// 削除してS3/S4から再記録する（docs/wireframes.md S11）。
// 単機能画面のためグローバルナビは表示しない（AppLayout未使用。DESIGN.md 10章 Navigation）。
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import DeleteCareLogModal from '@/Components/DeleteCareLogModal.vue';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';
import { useOccurredAtMaxTime } from '@/composables/useOccurredAtMaxTime';
import { useTrans } from '@/composables/useTrans';

interface EditableCareLog {
    id: string;
    // サーバー側が`careAction?->name`のnull安全演算子で取得しているため型としてはnullを許容する。
    careActionName: string | null;
    occurredDate: string;
    occurredTime: string;
    memo: string | null;
}

const props = defineProps<{
    careLog: EditableCareLog;
    // 「7日前〜今日」（docs/decisions.md §1.3）。YYYY-MM-DD形式。
    backdateFloorDate: string;
    todayDate: string;
    // `care_logs.date_help`の`:days`プレースホルダに渡す遡り可能日数。
    backdateDays: number;
}>();

const { t } = useTrans();
const { inputClass, labelClass, errorClass } = useFormFieldClasses();

// 実施日・実施時刻は個別の入力欄を持つが、サーバーへは結合済みの `occurred_at` として送る
// （`form.errors.occurred_at` をそのまま使えるようにするため。S10と同じ組み立て）。
const occurredDate = ref(props.careLog.occurredDate);
const occurredTime = ref(props.careLog.occurredTime);

const { maxTime } = useOccurredAtMaxTime(occurredDate, props.todayDate);

const form = useForm({
    occurred_at: '',
    // メモは現在値を初期表示し、空にして保存すれば削除できる（サーバー側の
    // `UpdateCareLogRequest::prepareForValidation()` が空文字をnullに正規化する）。
    memo: props.careLog.memo ?? '',
});

function submit(): void {
    form.occurred_at = `${occurredDate.value} ${occurredTime.value}:00`;
    form.patch(`/care-logs/${props.careLog.id}`);
}

// 削除は取り消し不能なため確認モーダルを挟む（DESIGN.md 10章 Dialogs and Notifications）。
const confirmingDeletion = ref(false);
const deleting = ref(false);

function destroy(): void {
    if (deleting.value) {
        return;
    }
    deleting.value = true;

    router.delete(`/care-logs/${props.careLog.id}`, {
        onFinish: () => {
            deleting.value = false;
        },
    });
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-text-primary">
        <div class="px-4 pt-6">
            <Link
                href="/history"
                class="inline-flex min-h-11 items-center text-body-sm text-secondary hover:text-text-primary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
            >
                {{ t('care_logs.back') }}
            </Link>
        </div>

        <div class="flex flex-1 flex-col items-center px-4 pb-10">
            <form class="w-full max-w-sm space-y-6" @submit.prevent="submit">
                <h1 class="text-center text-heading-l font-bold">{{ t('care_logs.edit_title') }}</h1>

                <!-- 育児行動は表示のみ（変更不可）。入力欄の形にすると変更できるように見えるため、
                     ラベル＋テキストで示す（docs/wireframes.md S11） -->
                <div class="space-y-1">
                    <p :class="labelClass">{{ t('care_logs.care_action_label') }}</p>
                    <p class="text-body text-text-primary">{{ careLog.careActionName }}</p>
                </div>

                <div class="space-y-1">
                    <label for="occurred_date" :class="labelClass">{{ t('care_logs.date_label') }}</label>
                    <input
                        id="occurred_date"
                        v-model="occurredDate"
                        type="date"
                        required
                        :min="backdateFloorDate"
                        :max="todayDate"
                        :class="[inputClass, form.errors.occurred_at ? 'border-error' : 'border-border']"
                    />
                    <p class="text-body-sm text-text-secondary">{{ t('care_logs.date_help', { days: backdateDays }) }}</p>
                </div>

                <div class="space-y-1">
                    <label for="occurred_time" :class="labelClass">{{ t('care_logs.time_label') }}</label>
                    <input
                        id="occurred_time"
                        v-model="occurredTime"
                        type="time"
                        required
                        :max="maxTime"
                        :class="[inputClass, form.errors.occurred_at ? 'border-error' : 'border-border']"
                    />
                </div>

                <p v-if="form.errors.occurred_at" :class="errorClass">{{ form.errors.occurred_at }}</p>

                <div class="space-y-1">
                    <label for="memo" :class="labelClass">{{ t('care_logs.memo_label') }}</label>
                    <textarea
                        id="memo"
                        v-model="form.memo"
                        maxlength="255"
                        rows="3"
                        :class="[inputClass, form.errors.memo ? 'border-error' : 'border-border']"
                    ></textarea>
                    <p v-if="form.errors.memo" :class="errorClass">{{ form.errors.memo }}</p>
                </div>

                <div class="flex flex-col gap-3">
                    <button
                        type="submit"
                        class="min-h-11 w-full rounded-xl bg-primary px-5 py-3 text-label font-semibold text-white hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:bg-border disabled:text-text-secondary"
                        :disabled="form.processing"
                    >
                        {{ t('care_logs.submit') }}
                    </button>

                    <!-- Destructive（DESIGN.md 10章 Buttons）：塗りにはせず枠線と文字色をError色にして、
                         誤タップの被害を抑える -->
                    <button
                        type="button"
                        class="min-h-11 w-full rounded-xl border border-error bg-transparent px-5 py-3 text-label font-semibold text-error focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                        @click="confirmingDeletion = true"
                    >
                        {{ t('care_logs.delete') }}
                    </button>
                </div>
            </form>
        </div>

        <DeleteCareLogModal
            v-if="confirmingDeletion"
            :processing="deleting"
            @confirm="destroy"
            @close="confirmingDeletion = false"
        />
    </div>
</template>
