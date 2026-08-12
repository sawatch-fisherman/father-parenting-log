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
        // `Unanswered`（未回答）はサーバー側でnullに正規化されて渡ってくる（S2の未選択と同じ表現にするため）。
        age_group: number | null;
        child_age_group: number | null;
    };
    ageGroups: Option[];
    childAgeGroups: Option[];
}>();

const { t } = useTrans();

const form = useForm({
    nickname: props.profile.nickname,
    age_group: (props.profile.age_group ?? '') as number | '',
    child_age_group: (props.profile.child_age_group ?? '') as number | '',
});

function submit(): void {
    form.patch('/settings/profile');
}
</script>

<template>
    <div class="flex min-h-screen flex-col bg-white text-gray-900 dark:bg-gray-900 dark:text-white">
        <div class="p-4">
            <!--
              settings.index（S7）はM8で実装予定のため、それまでの暫定リンク先として home へ戻す。
              SPA内遷移のため <Link> を使う（生の <a> だとフルページリロードになる）。
            -->
            <Link href="/" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                {{ t('profile.back') }}
            </Link>
        </div>

        <div class="flex flex-1 flex-col items-center px-4">
            <form class="w-full max-w-sm space-y-6" @submit.prevent="submit">
                <h1 class="text-center text-2xl font-semibold">{{ t('profile.edit_title') }}</h1>

                <ProfileFormFields
                    :form="form"
                    :age-groups="ageGroups"
                    :child-age-groups="childAgeGroups"
                    :nickname-label="t('profile.nickname_label')"
                />

                <button
                    type="submit"
                    class="w-full rounded-md bg-gray-900 px-6 py-3 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-200"
                    :disabled="form.processing"
                >
                    {{ t('profile.edit_submit') }}
                </button>
            </form>
        </div>
    </div>
</template>
