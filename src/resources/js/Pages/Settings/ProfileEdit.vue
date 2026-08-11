<script setup lang="ts">
// Inertia::render('Settings/ProfileEdit')（ProfileController@edit）が読み込むページコンポーネント（S8）。
// 閲覧専用画面は別途用意せず、この画面が閲覧を兼ねる。
import { Link, useForm } from '@inertiajs/vue3';
import ProfileFormFields from '@/Components/ProfileFormFields.vue';
import { useTrans } from '@/composables/useTrans';

interface Option {
    value: number;
    label: string;
}

const props = defineProps<{
    profile: {
        nickname: string;
        age_group: number;
        child_age_group: number;
    };
    ageGroups: Option[];
    childAgeGroups: Option[];
}>();

const { t } = useTrans();

const form = useForm({
    nickname: props.profile.nickname,
    age_group: props.profile.age_group as number | '',
    child_age_group: props.profile.child_age_group as number | '',
});

function submit(): void {
    form.patch('/settings/profile');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-text-primary">
        <div class="px-4 pt-6">
            <!--
              settings.index（S7）はM8で実装予定のため、それまでの暫定リンク先として home へ戻す。
              SPA内遷移のため <Link> を使う（生の <a> だとフルページリロードになる）。
            -->
            <Link href="/" class="text-body-sm text-text-secondary hover:text-text-primary">
                {{ t('profile.back') }}
            </Link>
        </div>

        <div class="flex flex-1 flex-col items-center px-4">
            <form class="w-full max-w-sm space-y-6" @submit.prevent="submit">
                <h1 class="text-center text-heading-l font-bold">{{ t('profile.edit_title') }}</h1>

                <ProfileFormFields
                    :form="form"
                    :age-groups="ageGroups"
                    :child-age-groups="childAgeGroups"
                    :nickname-label="t('profile.nickname_label')"
                />

                <button
                    type="submit"
                    class="w-full rounded-xl bg-primary px-5 py-3 text-label font-semibold text-white hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25 disabled:pointer-events-none disabled:bg-border disabled:text-text-secondary"
                    :disabled="form.processing"
                >
                    {{ t('profile.edit_submit') }}
                </button>
            </form>
        </div>
    </div>
</template>
