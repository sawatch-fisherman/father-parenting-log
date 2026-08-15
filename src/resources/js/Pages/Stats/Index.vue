<script setup lang="ts">
// Inertia::render('Stats/Index')（routes/web.php の暫定プレースホルダ）が読み込むS12のページ。
// 実装はM7（docs/implementation-plan.md「M7 集計（S12）」）で行う。M3ではグローバルナビから
// 遷移できることだけを担保する。
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';

const { t } = useTrans();

defineOptions({
    layout: [AppLayout, { active: 'stats' }],
});
</script>

<template>
    <div>
        <h1 class="text-heading-l font-bold">{{ t('nav.stats') }}</h1>
        <p class="mt-4 text-body text-text-secondary">{{ t('stats.placeholder') }}</p>
    </div>
</template>
