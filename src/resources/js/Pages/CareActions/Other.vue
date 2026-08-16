<script setup lang="ts">
// Inertia::render('CareActions/Other')（CareActionController@other）が読み込むS4のページ。
// 一覧の各行タップで必ずS10（実施日時指定画面）へ遷移し、即記録は行わない
// （docs/wireframes.md S4）。単機能画面のためグローバルナビは表示しない（AppLayout未使用）。
import { Link } from '@inertiajs/vue3';
import { useTrans } from '@/composables/useTrans';

interface CareAction {
    id: number;
    name: string;
}

defineProps<{
    careActions: CareAction[];
}>();

const { t } = useTrans();
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-text-primary">
        <!-- デスクトップの最大幅960px（DESIGN.md 8.1節）。AppLayout本体（8.1/8.3節）と同じ幅に揃える -->
        <div class="mx-auto w-full max-w-[960px] px-4 pt-6 md:px-8">
            <Link
                href="/"
                class="inline-flex min-h-11 items-center text-body-sm text-secondary hover:text-text-primary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
            >
                {{ t('care_actions.back') }}
            </Link>
        </div>

        <div class="mx-auto w-full max-w-[960px] px-4 pb-10 md:px-8">
            <h1 class="mb-4 text-heading-l font-bold">{{ t('care_actions.other_title') }}</h1>

            <ul v-if="careActions.length" class="divide-y divide-border">
                <li v-for="careAction in careActions" :key="careAction.id">
                    <Link
                        :href="`/care-logs/create?care_action_id=${careAction.id}`"
                        class="flex min-h-11 items-center justify-between py-4 text-body text-text-primary focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                    >
                        <span>{{ careAction.name }}</span>
                        <span aria-hidden="true" class="text-text-secondary">›</span>
                    </Link>
                </li>
            </ul>

            <!-- Phase 2でカスタム育児行動をすべてピン留めすると発生しうる空状態（DESIGN.md 11章 Empty） -->
            <div v-else class="flex flex-col items-center gap-4 py-16 text-center">
                <p class="text-body text-text-secondary">{{ t('care_actions.empty') }}</p>
                <Link
                    href="/"
                    class="rounded-xl bg-primary px-5 py-3 text-label font-semibold text-white hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-[3px] focus-visible:ring-primary/25"
                >
                    {{ t('care_actions.empty_cta') }}
                </Link>
            </div>
        </div>
    </div>
</template>
