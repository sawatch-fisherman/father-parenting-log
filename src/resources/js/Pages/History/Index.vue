<script setup lang="ts">
// Inertia::render('History/Index')（routes/web.php の暫定プレースホルダ）が読み込むS13のページ。
// 実装はM6（docs/implementation-plan.md「M6 履歴（S13, S11）」）で行う。M3ではグローバルナビから
// 遷移できることだけを担保する。
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';

const { t } = useTrans();

defineOptions({
    layout: [AppLayout, { active: 'history' }],
});
</script>

<template>
    <div>
        <h1 class="text-heading-l font-bold">{{ t('nav.history') }}</h1>
        <p class="mt-4 text-body text-text-secondary">{{ t('history.placeholder') }}</p>
    </div>
</template>
