<script setup lang="ts">
// Inertia::render('Settings/Index')（routes/web.php の暫定プレースホルダ）が読み込むS7のページ。
// 実装はM8（docs/implementation-plan.md「M8 設定（S7, S9）」）で行う。M3ではグローバルナビから
// 遷移できることだけを担保する。
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTrans } from '@/composables/useTrans';

const { t } = useTrans();

defineOptions({
    layout: [AppLayout, { active: 'settings' }],
});
</script>

<template>
    <div>
        <h1 class="text-heading-l font-bold">{{ t('nav.settings') }}</h1>
        <p class="mt-4 text-body text-text-secondary">{{ t('settings.placeholder') }}</p>
    </div>
</template>
