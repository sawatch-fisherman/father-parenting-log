<script setup lang="ts">
// Inertia::render('CareLogs/Create')（CareLogController@create）が読み込むS10のページ。
// S3の長押し、またはS4の項目タップから遷移する（docs/wireframes.md S10）。
// 単機能画面のためグローバルナビは表示しない（AppLayout未使用）。
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import CareLogFormFields from '@/Components/CareLogFormFields.vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';
import { currentTimeString } from '@/composables/useOccurredAtMaxTime';
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
    // `care_logs.date_help`の`:days`プレースホルダに渡す遡り可能日数（`config('totoops.care_log.backdate_days')`）。
    backdateDays: number;
}>();

const { t } = useTrans();
const { errorClass } = useFormFieldClasses();
const { primaryButtonClass } = useButtonClasses();

// 実施日・実施時刻は個別の入力欄（<input type="date">/<input type="time">）を持つが、
// サーバーへは結合済みの `occurred_at` として送る。`form.errors.occurred_at` を
// そのまま使えるよう、`occurred_at` はここではローカルrefにせずuseFormのフィールドにする。
// 入力欄自体はS11（ログ編集）と共通の `CareLogFormFields` に置いている。
const occurredDate = ref(props.todayDate);
const occurredTime = ref(currentTimeString());

const form = useForm({
    care_action_id: props.careAction.id,
    occurred_at: '',
    // 空欄（メモなし）は `StoreCareLogRequest::prepareForValidation()` がサーバー側でnullに正規化する。
    memo: '',
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

                <!-- `care_action_id`はS4のリンク経由の固定値で通常は入力欄を持たないが、
                     万一サーバー側で無効と判定された場合に無反応にならないよう表示する -->
                <p v-if="form.errors.care_action_id" :class="errorClass">{{ form.errors.care_action_id }}</p>

                <CareLogFormFields
                    v-model:occurred-date="occurredDate"
                    v-model:occurred-time="occurredTime"
                    :form="form"
                    :backdate-floor-date="backdateFloorDate"
                    :today-date="todayDate"
                    :backdate-days="backdateDays"
                />

                <button
                    type="submit"
                    :class="['w-full', primaryButtonClass]"
                    :disabled="form.processing"
                >
                    {{ t('care_logs.submit') }}
                </button>
            </form>
        </div>
    </div>
</template>
