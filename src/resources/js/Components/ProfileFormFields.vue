<script setup lang="ts">
// S2（登録）・S8（編集）で共用するプロフィール入力欄。`form`はInertiaの useForm() の戻り値を
// そのまま受け取り、子から直接ミューテートする（同じリアクティブオブジェクトを参照するため反映される）。
import { useTrans } from '@/composables/useTrans';

interface Option {
    value: number;
    label: string;
}

interface ProfileFormLike {
    nickname: string;
    age_group: number | '';
    child_age_group: number | '';
    errors: {
        nickname?: string;
        age_group?: string;
        child_age_group?: string;
    };
}

defineProps<{
    form: ProfileFormLike;
    ageGroups: Option[];
    childAgeGroups: Option[];
    nicknameLabel: string;
}>();

const { t } = useTrans();

// DESIGN.md 10章 Forms の入力欄仕様。3つのフィールドで同じ指定を繰り返さないため定数に切り出す。
// 枠線色だけはエラー有無で切り替わるので、ここには含めず各フィールド側で付ける（11章 Error）。
const inputClass =
    'w-full rounded-md border bg-surface px-4 py-3 text-body text-text-primary focus:border-primary focus:outline-none focus:ring-[3px] focus:ring-primary/25';

const labelClass = 'block text-label font-semibold text-text-primary';

const errorClass = 'text-body-sm text-error';
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-1">
            <label for="nickname" :class="labelClass">{{ nicknameLabel }}</label>
            <input
                id="nickname"
                v-model="form.nickname"
                type="text"
                maxlength="50"
                :class="[inputClass, form.errors.nickname ? 'border-error' : 'border-border']"
            />
            <p v-if="form.errors.nickname" :class="errorClass">{{ form.errors.nickname }}</p>
        </div>

        <div class="space-y-1">
            <label for="age_group" :class="labelClass">{{ t('profile.age_group_label') }}</label>
            <select
                id="age_group"
                v-model="form.age_group"
                :class="[inputClass, form.errors.age_group ? 'border-error' : 'border-border']"
            >
                <option value="">{{ t('profile.unselected') }}</option>
                <option v-for="option in ageGroups" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <p v-if="form.errors.age_group" :class="errorClass">{{ form.errors.age_group }}</p>
        </div>

        <div class="space-y-1">
            <label for="child_age_group" :class="labelClass">{{ t('profile.child_age_group_label') }}</label>
            <select
                id="child_age_group"
                v-model="form.child_age_group"
                :class="[inputClass, form.errors.child_age_group ? 'border-error' : 'border-border']"
            >
                <option value="">{{ t('profile.unselected') }}</option>
                <option v-for="option in childAgeGroups" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <p v-if="form.errors.child_age_group" :class="errorClass">
                {{ form.errors.child_age_group }}
            </p>
        </div>
    </div>
</template>
