<script setup lang="ts">
// Inertia::render('Settings/Index')（SettingsController@index）が読み込むS7のページ。
// プロフィール編集・ピン留め設定・ログアウトへの入口を並べるハブ画面（docs/wireframes.md S7）。
// 全体集計への導線はここに置かない（S12全期間タブに一本化。docs/decisions.md §1.3）。
import { Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import LocaleToggle from '@/Components/LocaleToggle.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useButtonClasses } from '@/composables/useButtonClasses';
import { useTrans } from '@/composables/useTrans';

const { t } = useTrans();
const { secondaryButtonClass, focusRing } = useButtonClasses();

defineOptions({
    layout: [AppLayout, { active: 'settings' }],
});

const loggingOut = ref(false);

function logout(): void {
    if (loggingOut.value) {
        return;
    }
    loggingOut.value = true;

    router.post('/logout', {}, {
        onFinish: () => {
            loggingOut.value = false;
        },
    });
}
</script>

<template>
    <div>
        <h1 class="mb-6 text-heading-l font-bold">{{ t('settings.title') }}</h1>

        <div class="divide-y divide-border border-t border-border">
            <div class="flex min-h-11 items-center justify-between py-4">
                <span class="text-body text-text-primary">{{ t('settings.language_label') }}</span>
                <LocaleToggle />
            </div>

            <Link
                href="/settings/profile"
                :class="['flex min-h-11 items-center justify-between py-4 text-body text-text-primary', focusRing]"
            >
                <span>{{ t('settings.profile_link') }}</span>
                <span aria-hidden="true" class="text-text-secondary">›</span>
            </Link>

            <Link
                href="/settings/slots"
                :class="['flex min-h-11 items-center justify-between py-4 text-body text-text-primary', focusRing]"
            >
                <span>{{ t('settings.slots_link') }}</span>
                <span aria-hidden="true" class="text-text-secondary">›</span>
            </Link>

            <!-- Phase 2以降の“器”のプレースホルダ（未活性表現。docs/wireframes.md S7） -->
            <div class="flex min-h-11 items-center justify-between py-4 text-body text-text-secondary">
                <span>{{ t('settings.custom_care_actions_link') }}</span>
                <span class="text-body-sm">{{ t('settings.coming_soon') }}</span>
            </div>
            <div class="flex min-h-11 items-center justify-between py-4 text-body text-text-secondary">
                <span>{{ t('settings.graduation_link') }}</span>
                <span class="text-body-sm">{{ t('settings.coming_soon') }}</span>
            </div>
            <div class="flex min-h-11 items-center justify-between py-4 text-body text-text-secondary">
                <span>{{ t('settings.ads_link') }}</span>
                <span class="text-body-sm">{{ t('settings.coming_soon') }}</span>
            </div>
        </div>

        <button
            type="button"
            :class="['mt-6 w-full', secondaryButtonClass]"
            :disabled="loggingOut"
            @click="logout"
        >
            {{ t('settings.logout') }}
        </button>
    </div>
</template>
