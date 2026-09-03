<script setup lang="ts">
// S10（実施日時指定）・S11（ログ編集）で共用する育児ログの入力欄（実施日／実施時刻／メモ）。
// 2画面はどちらも「日付と時刻を別々の入力欄で受け取り、サーバーへは結合した `occurred_at` として
// 送る」という同じ構造で、`ProfileFormFields.vue`（S2・S8共用）と同じ理由でここに寄せる：
// マークアップを各ページに書くと、DESIGN.md 10章 Forms の変更時に片方だけ追従漏れが起きる。
//
// 実施日・実施時刻は`defineModel`で親と双方向に束ねる。`occurred_at`への結合は送信時にしか
// 必要がなく、結合済みの文字列を親が`useForm`のフィールドとして持つ方が
// `form.errors.occurred_at` をそのまま表示できるため（結合自体は親の`submit()`が行う）。
import { useFormFieldClasses } from '@/composables/useFormFieldClasses';
import { useOccurredAtMaxTime } from '@/composables/useOccurredAtMaxTime';
import { useTrans } from '@/composables/useTrans';

interface CareLogFormLike {
    memo: string;
    errors: {
        occurred_at?: string;
        memo?: string;
    };
}

const props = defineProps<{
    form: CareLogFormLike;
    // 実施日の選択可能範囲「7日前〜今日」（docs/decisions.md §1.3）。YYYY-MM-DD形式。
    backdateFloorDate: string;
    todayDate: string;
    // `care_logs.date_help`の`:days`プレースホルダに渡す遡り可能日数
    // （`config('totoops.care_log.backdate_days')`）。
    backdateDays: number;
}>();

const occurredDate = defineModel<string>('occurredDate', { required: true });
const occurredTime = defineModel<string>('occurredTime', { required: true });

const { t } = useTrans();

// DESIGN.md 10章 Forms の入力欄仕様（`useFormFieldClasses` に集約）。
// 枠線色だけはエラー有無で切り替わるので、ここには含めず各フィールド側で付ける（11章 Error）。
const { inputClass, labelClass, errorClass } = useFormFieldClasses();

// 実施時刻の上限（実施日が「今日」のときだけ「現在＋5分」）。UI側の制限はサーバー側
// バリデーションの代替ではなく二重担保（docs/wireframes.md S10）。
const { maxTime } = useOccurredAtMaxTime(occurredDate, props.todayDate);
</script>

<template>
    <div class="space-y-6">
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
    </div>
</template>
