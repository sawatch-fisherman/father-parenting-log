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
</script>

<template>
    <div class="space-y-6">
        <div class="space-y-1">
            <label for="nickname" class="block text-sm font-medium">{{ nicknameLabel }}</label>
            <input
                id="nickname"
                v-model="form.nickname"
                type="text"
                maxlength="50"
                class="w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800"
            />
            <p v-if="form.errors.nickname" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.nickname }}</p>
        </div>

        <div class="space-y-1">
            <label for="age_group" class="block text-sm font-medium">{{ t('profile.age_group_label') }}</label>
            <select
                id="age_group"
                v-model="form.age_group"
                class="w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800"
            >
                <option value="">{{ t('profile.unselected') }}</option>
                <option v-for="option in ageGroups" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <p v-if="form.errors.age_group" class="text-sm text-red-600 dark:text-red-400">{{ form.errors.age_group }}</p>
        </div>

        <div class="space-y-1">
            <label for="child_age_group" class="block text-sm font-medium">{{ t('profile.child_age_group_label') }}</label>
            <select
                id="child_age_group"
                v-model="form.child_age_group"
                class="w-full rounded-md border border-gray-300 px-3 py-2 dark:border-gray-600 dark:bg-gray-800"
            >
                <option value="">{{ t('profile.unselected') }}</option>
                <option v-for="option in childAgeGroups" :key="option.value" :value="option.value">{{ option.label }}</option>
            </select>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ t('profile.child_age_group_note') }}</p>
            <p v-if="form.errors.child_age_group" class="text-sm text-red-600 dark:text-red-400">
                {{ form.errors.child_age_group }}
            </p>
        </div>
    </div>
</template>
