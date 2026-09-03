<script setup lang="ts">
// Inertia::render('CareLogs/Edit')（CareLogController@edit）が読み込むS11のページ。
// S13の各行「…」から遷移し、`occurred_at` と `memo` だけを変更できる。育児行動を変えたい場合は
// 削除してS3/S4から再記録する（docs/wireframes.md S11）。
// 単機能画面のためグローバルナビは表示しない（AppLayout未使用。DESIGN.md 10章 Navigation）。
import { Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import CareLogFormFields from '@/Components/CareLogFormFields.vue';
import DeleteCareLogModal from '@/Components/DeleteCareLogModal.vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';
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
const { labelClass } = useFormFieldClasses();
const { primaryButtonClass, destructiveButtonClass } = useButtonClasses();

// 実施日・実施時刻は個別の入力欄を持つが、サーバーへは結合済みの `occurred_at` として送る
// （`form.errors.occurred_at` をそのまま使えるようにするため。入力欄はS10と共通の
// `CareLogFormFields` に置いている）。
const occurredDate = ref(props.careLog.occurredDate);
const occurredTime = ref(props.careLog.occurredTime);

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

                <CareLogFormFields
                    v-model:occurred-date="occurredDate"
                    v-model:occurred-time="occurredTime"
                    :form="form"
                    :backdate-floor-date="backdateFloorDate"
                    :today-date="todayDate"
                    :backdate-days="backdateDays"
                />

                <div class="flex flex-col gap-3">
                    <button
                        type="submit"
                        :class="['min-h-11 w-full', primaryButtonClass]"
                        :disabled="form.processing"
                    >
                        {{ t('care_logs.submit') }}
                    </button>

                    <button
                        type="button"
                        :class="['min-h-11 w-full', destructiveButtonClass]"
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
