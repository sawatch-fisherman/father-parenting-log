<script setup lang="ts">
// Inertia::render('Profile/Register')（ProfileController@create）が読み込むページコンポーネント（S2）
import { useForm } from '@inertiajs/vue3';
import ProfileFormFields from '@/Components/ProfileFormFields.vue';
import { useTrans } from '@/composables/useTrans';

interface Option {
    value: number;
    label: string;
}

defineProps<{
    ageGroups: Option[];
    childAgeGroups: Option[];
}>();

const { t } = useTrans();

const form = useForm({
    nickname: '',
    age_group: '' as number | '',
    child_age_group: '' as number | '',
});

function submit(): void {
    form.post('/profile');
}
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-white px-4 text-gray-900 dark:bg-gray-900 dark:text-white">
        <form class="w-full max-w-sm space-y-6" @submit.prevent="submit">
            <h1 class="text-center text-2xl font-semibold">{{ t('profile.register_title') }}</h1>

            <ProfileFormFields
                :form="form"
                :age-groups="ageGroups"
                :child-age-groups="childAgeGroups"
                :nickname-label="t('profile.nickname_required_label')"
            />

            <button
                type="submit"
                class="w-full rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                :disabled="form.processing"
            >
                {{ t('profile.register_submit') }}
            </button>
        </form>
    </div>
</template>
