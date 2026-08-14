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
    <div class="flex min-h-screen flex-col items-center bg-background px-4 pt-6 text-text-primary">
        <form class="w-full max-w-sm space-y-6" @submit.prevent="submit">
            <h1 class="text-center text-display font-bold">{{ t('profile.register_title') }}</h1>

            <ProfileFormFields
                :form="form"
                :age-groups="ageGroups"
                :child-age-groups="childAgeGroups"
                :nickname-label="t('profile.nickname_required_label')"
            />

            <button
                type="submit"
                class="w-full rounded-xl bg-primary px-5 py-3 text-label font-semibold text-white hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25 disabled:cursor-not-allowed disabled:bg-border disabled:text-text-secondary"
                :disabled="form.processing"
            >
                {{ t('profile.register_submit') }}
            </button>
        </form>
    </div>
</template>
